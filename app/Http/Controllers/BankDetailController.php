<?php

namespace App\Http\Controllers;

use App\Models\BankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankDetailController extends Controller
{
    public function index(Request $request)
    {
        $bankDetails = $request->user()
            ->bankDetails()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'bank_details' => $bankDetails
        ]);
    }

    public function show(Request $request, $id)
    {
        $bankDetail = $request->user()
            ->bankDetails()
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'bank_detail' => $bankDetail
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:20',
            'bank_code' => 'nullable|string|max:10',
            'currency' => 'sometimes|string|max:3',
            'is_default' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if account number already exists for this user
        $existingAccount = $request->user()
            ->bankDetails()
            ->where('account_number', $request->account_number)
            ->first();

        if ($existingAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bank account already exists'
            ], 422);
        }

        $data = [
            'user_id' => $request->user()->id,
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'bank_code' => $request->bank_code,
            'currency' => $request->currency ?? 'NGN',
        ];

        // If setting as default, remove default from other bank details
        if ($request->is_default) {
            $request->user()
                ->bankDetails()
                ->update(['is_default' => false]);
            
            $data['is_default'] = true;
        }

        $bankDetail = BankDetail::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Bank details added successfully',
            'bank_detail' => $bankDetail
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $bankDetail = $request->user()
            ->bankDetails()
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'bank_name' => 'sometimes|required|string|max:255',
            'account_name' => 'sometimes|required|string|max:255',
            'account_number' => 'sometimes|required|string|max:20',
            'bank_code' => 'nullable|string|max:10',
            'currency' => 'sometimes|string|max:3',
            'is_default' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if account number already exists for this user (excluding current record)
        if ($request->has('account_number') && $request->account_number !== $bankDetail->account_number) {
            $existingAccount = $request->user()
                ->bankDetails()
                ->where('account_number', $request->account_number)
                ->where('id', '!=', $id)
                ->first();

            if ($existingAccount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bank account already exists'
                ], 422);
            }
        }

        $data = $request->only(['bank_name', 'account_name', 'account_number', 'bank_code', 'currency']);

        // If setting as default, remove default from other bank details
        if ($request->is_default && !$bankDetail->is_default) {
            $request->user()
                ->bankDetails()
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
            
            $data['is_default'] = true;
        }

        $bankDetail->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Bank details updated successfully',
            'bank_detail' => $bankDetail
        ]);
    }

    public function setDefault(Request $request, $id)
    {
        $bankDetail = $request->user()
            ->bankDetails()
            ->findOrFail($id);

        // Remove default from other bank details
        $request->user()
            ->bankDetails()
            ->where('id', '!=', $id)
            ->update(['is_default' => false]);

        // Set this as default
        $bankDetail->update(['is_default' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Bank account set as default successfully',
            'bank_detail' => $bankDetail
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $bankDetail = $request->user()
            ->bankDetails()
            ->findOrFail($id);

        // Prevent deletion of default bank account if it's the only one
        if ($bankDetail->is_default && $request->user()->bankDetails()->count() > 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete default bank account. Please set another account as default first.'
            ], 422);
        }

        $bankDetail->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Bank details deleted successfully'
        ]);
    }

    public function verifyAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string|max:20',
            'bank_code' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Here you would typically integrate with a bank verification API
        // For now, we'll simulate a successful verification
        $verifiedAccountName = "Verified Account Holder"; // This would come from the API

        return response()->json([
            'status' => 'success',
            'message' => 'Account verification successful',
            'data' => [
                'account_number' => $request->account_number,
                'account_name' => $verifiedAccountName,
                'bank_code' => $request->bank_code
            ]
        ]);
    }
}