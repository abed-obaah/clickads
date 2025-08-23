<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function deposit(User $user, $amount, $description = null, $metadata = [])
    {
        // Start database transaction
        return DB::transaction(function () use ($user, $amount, $description, $metadata) {
            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'description' => $description ?? 'Wallet deposit',
                'status' => 'completed',
                'reference' => 'DEP' . Str::upper(Str::random(10)),
                'metadata' => $metadata
            ]);

            // Update user balance
            $user->balance += $amount;
            $user->save();

            // Create notification
            Notification::create([
                'user_id' => $user->id,
                'type' => 'transaction.deposit',
                'title' => 'Deposit Received',
                'message' => 'Your deposit of ' . config('app.currency', '₦') . number_format($amount, 2) . ' was successful. New balance: ' . config('app.currency', '₦') . number_format($user->balance, 2),
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'new_balance' => $user->balance
                ]
            ]);

            return [
                'transaction' => $transaction,
                'new_balance' => $user->balance
            ];
        });
    }

    public function withdraw(User $user, $amount, $description = null, $metadata = [])
    {
        // Check if user has sufficient balance
        if ($user->balance < $amount) {
            throw new \Exception('Insufficient funds');
        }

        // Start database transaction
        return DB::transaction(function () use ($user, $amount, $description, $metadata) {
            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => $description ?? 'Wallet withdrawal',
                'status' => 'completed',
                'reference' => 'WDL' . Str::upper(Str::random(10)),
                'metadata' => $metadata
            ]);

            // Update user balance
            $user->balance -= $amount;
            $user->save();

            // Create notification
            Notification::create([
                'user_id' => $user->id,
                'type' => 'transaction.withdrawal',
                'title' => 'Withdrawal Processed',
                'message' => 'Your withdrawal of ' . config('app.currency', '₦') . number_format($amount, 2) . ' was successful. New balance: ' . config('app.currency', '₦') . number_format($user->balance, 2),
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'new_balance' => $user->balance
                ]
            ]);

            return [
                'transaction' => $transaction,
                'new_balance' => $user->balance
            ];
        });
    }

    public function getTransactionHistory(User $user, $filters = [], $limit = 10)
    {
        $query = $user->transactions()->orderBy('created_at', 'desc');
        
        // Apply filters if provided
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->paginate($limit);
    }
}