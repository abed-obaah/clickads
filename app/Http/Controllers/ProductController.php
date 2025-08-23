<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);

        $validator = Validator::make($request->all(), [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $store->products()->where('is_available', true);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $products = $query->orderBy('created_at', 'desc')
                         ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => 'success',
            'products' => $products
        ]);
    }

    public function show($storeId, $productId)
    {
        $product = Product::with('store')
                         ->where('store_id', $storeId)
                         ->findOrFail($productId);

        return response()->json([
            'status' => 'success',
            'product' => $product
        ]);
    }

    public function store(Request $request, $storeId)
    {
        $store = $request->user()->stores()->findOrFail($storeId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'image' => 'nullable|string' // Base64 encoded image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'store_id' => $store->id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'quantity' => $request->quantity,
        ];

        // Handle base64 image
        if ($request->has('image') && !empty($request->image)) {
            $imageData = $this->processBase64Image($request->image);
            if ($imageData) {
                $data['image'] = $imageData['data'];
                $data['image_mime'] = $imageData['mime'];
            }
        }

        $product = Product::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    public function update(Request $request, $storeId, $productId)
    {
        $store = $request->user()->stores()->findOrFail($storeId);
        $product = $store->products()->findOrFail($productId);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'image' => 'nullable|string', // Base64 encoded image
            'is_available' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'description', 'price', 'quantity', 'is_available']);

        // Handle base64 image
        if ($request->has('image')) {
            if (empty($request->image)) {
                // Clear image if empty string is provided
                $data['image'] = null;
                $data['image_mime'] = null;
            } else {
                $imageData = $this->processBase64Image($request->image);
                if ($imageData) {
                    $data['image'] = $imageData['data'];
                    $data['image_mime'] = $imageData['mime'];
                }
            }
        }

        $product->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    public function destroy(Request $request, $storeId, $productId)
    {
        $store = $request->user()->stores()->findOrFail($storeId);
        $product = $store->products()->findOrFail($productId);
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Process base64 image and extract data and mime type
     */
    private function processBase64Image($base64Image)
    {
        // Check if the base64 string contains data URI scheme
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $mime = 'image/' . $matches[1];
            $data = substr($base64Image, strpos($base64Image, ',') + 1);
            $data = base64_decode($data);
            
            // Validate it's a proper image
            if (@getimagesizefromstring($data)) {
                return [
                    'data' => base64_encode($data), // Store as base64 string
                    'mime' => $mime
                ];
            }
        }
        
        return null;
    }
}