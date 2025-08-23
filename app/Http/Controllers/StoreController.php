<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'user_id' => 'sometimes|exists:users,id',
            'search' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Store::with('user')->where('is_active', true);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $stores = $query->orderBy('created_at', 'desc')
                       ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => 'success',
            'stores' => $stores
        ]);
    }

    public function show($id)
    {
        $store = Store::with(['user', 'products' => function($query) {
            $query->where('is_available', true);
        }])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'store' => $store
        ]);
    }

    public function userStores(Request $request)
    {
        $stores = $request->user()
                         ->stores()
                         ->withCount('products')
                         ->orderBy('created_at', 'desc')
                         ->paginate(10);

        return response()->json([
            'status' => 'success',
            'stores' => $stores
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
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
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
        ];

        // Handle base64 image
        if ($request->has('image') && !empty($request->image)) {
            $imageData = $this->processBase64Image($request->image);
            if ($imageData) {
                $data['image'] = $imageData['data'];
                $data['image_mime'] = $imageData['mime'];
            }
        }

        $store = Store::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Store created successfully',
            'store' => $store
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $store = $request->user()->stores()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string', // Base64 encoded image
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'description', 'is_active']);

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

        $store->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Store updated successfully',
            'store' => $store
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $store = $request->user()->stores()->findOrFail($id);
        $store->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Store deleted successfully'
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