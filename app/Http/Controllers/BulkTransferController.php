<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\BulkTransferService;

class BulkTransferController extends Controller
{
    protected $bulkTransferService;

    public function __construct(BulkTransferService $bulkTransferService)
    {
        $this->bulkTransferService = $bulkTransferService;
    }

    public function initiateBulkTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*.recipient_id' => 'required|exists:users,id',
            'recipients.*.amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255'
        ], [
            'recipients.*.recipient_id.exists' => 'The selected recipient is invalid.',
            'recipients.*.amount.min' => 'Each transfer amount must be at least 0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user is trying to transfer to themselves
        $selfTransfer = collect($request->recipients)->contains(function ($recipient) use ($request) {
            return $recipient['recipient_id'] == $request->user()->id;
        });

        if ($selfTransfer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot transfer to yourself'
            ], 422);
        }

        try {
            $user = $request->user();
            $bulkTransfer = $this->bulkTransferService->processBulkTransfer(
                $user, 
                $request->recipients, 
                $request->description
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk transfer processed successfully',
                'bulk_transfer' => $bulkTransfer
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bulk transfer failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function bulkTransferHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|in:pending,processing,completed,failed,partial'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $perPage = $request->per_page ?? 10;
        $transfers = $this->bulkTransferService->getBulkTransferHistory(
            $request->user(), 
            $perPage
        );

        return response()->json([
            'status' => 'success',
            'bulk_transfers' => $transfers
        ]);
    }

    public function bulkTransferDetails(Request $request, $id)
    {
        $transfer = $this->bulkTransferService->getBulkTransferDetails(
            $id, 
            $request->user()
        );

        return response()->json([
            'status' => 'success',
            'bulk_transfer' => $transfer
        ]);
    }

    public function calculateTotal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*.amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $totalAmount = collect($request->recipients)->sum('amount');
        $recipientCount = count($request->recipients);

        return response()->json([
            'status' => 'success',
            'total_amount' => $totalAmount,
            'recipient_count' => $recipientCount,
            'currency' => config('app.currency', '₦')
        ]);
    }
}