<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\TransactionService;

class WalletController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function getBalance(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'balance' => $request->user()->balance
        ]);
    }

    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $result = $this->transactionService->deposit(
                $user, 
                $request->amount, 
                $request->description
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Deposit successful',
                'transaction' => $result['transaction'],
                'new_balance' => $result['new_balance']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Deposit failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $result = $this->transactionService->withdraw(
                $user, 
                $request->amount, 
                $request->description
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Withdrawal successful',
                'transaction' => $result['transaction'],
                'new_balance' => $result['new_balance']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Withdrawal failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function transactionHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|in:deposit,withdrawal,transfer,payment',
            'status' => 'sometimes|in:pending,completed,failed,cancelled',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $perPage = $request->per_page ?? 10;
        $filters = $request->only(['type', 'status', 'start_date', 'end_date']);
        
        $transactions = $this->transactionService->getTransactionHistory(
            $request->user(), 
            $filters,
            $perPage
        );

        return response()->json([
            'status' => 'success',
            'transactions' => $transactions
        ]);
    }

    public function notifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|string',
            'is_read' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->user()->notifications()->orderBy('created_at', 'desc');
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        $notifications = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications
        ]);
    }

    public function markNotificationAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }
}