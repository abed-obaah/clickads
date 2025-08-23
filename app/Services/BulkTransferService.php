<?php

namespace App\Services;

use App\Models\BulkTransfer;
use App\Models\BulkTransferRecipient;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkTransferService
{
    public function processBulkTransfer(User $sender, array $recipients, $description = null)
    {
        return DB::transaction(function () use ($sender, $recipients, $description) {
            // Calculate total amount
            $totalAmount = collect($recipients)->sum('amount');

            // Check if sender has sufficient balance
            if ($sender->balance < $totalAmount) {
                throw new \Exception('Insufficient funds for bulk transfer');
            }

            // Create bulk transfer record
            $bulkTransfer = BulkTransfer::create([
                'user_id' => $sender->id,
                'reference' => 'BULK' . Str::upper(Str::random(10)),
                'total_amount' => $totalAmount,
                'recipient_count' => count($recipients),
                'status' => 'processing',
                'description' => $description,
                'metadata' => [
                    'recipients_count' => count($recipients),
                    'total_amount' => $totalAmount
                ]
            ]);

            // Create recipient records and process transfers
            $successfulTransfers = 0;
            $failedTransfers = 0;

            foreach ($recipients as $recipientData) {
                try {
                    $recipient = User::find($recipientData['recipient_id']);
                    
                    if (!$recipient) {
                        throw new \Exception('Recipient not found');
                    }

                    // Create recipient record
                    $transferRecipient = BulkTransferRecipient::create([
                        'bulk_transfer_id' => $bulkTransfer->id,
                        'recipient_id' => $recipient->id,
                        'amount' => $recipientData['amount'],
                        'status' => 'pending'
                    ]);

                    // Process individual transfer
                    $this->processIndividualTransfer($sender, $recipient, $recipientData['amount'], $bulkTransfer, $transferRecipient);
                    
                    $successfulTransfers++;

                } catch (\Exception $e) {
                    $failedTransfers++;

                    // Mark as failed
                    if (isset($transferRecipient)) {
                        $transferRecipient->update([
                            'status' => 'failed',
                            'failure_reason' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Update bulk transfer status
            $finalStatus = 'completed';
            if ($failedTransfers > 0) {
                $finalStatus = $successfulTransfers > 0 ? 'partial' : 'failed';
            }

            $bulkTransfer->update([
                'status' => $finalStatus,
                'metadata' => array_merge($bulkTransfer->metadata, [
                    'successful_transfers' => $successfulTransfers,
                    'failed_transfers' => $failedTransfers
                ])
            ]);

            return $bulkTransfer;
        });
    }

    private function processIndividualTransfer($sender, $recipient, $amount, $bulkTransfer, $transferRecipient)
    {
        DB::transaction(function () use ($sender, $recipient, $amount, $bulkTransfer, $transferRecipient) {
            // Deduct from sender
            $sender->balance -= $amount;
            $sender->save();

            // Add to recipient
            $recipient->balance += $amount;
            $recipient->save();

            // Create transaction for sender (withdrawal)
            Transaction::create([
                'user_id' => $sender->id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'description' => 'Bulk transfer to ' . $recipient->name . ' (Ref: ' . $bulkTransfer->reference . ')',
                'status' => 'completed',
                'reference' => 'BTO' . Str::upper(Str::random(10)),
                'metadata' => [
                    'bulk_transfer_id' => $bulkTransfer->id,
                    'recipient_id' => $recipient->id,
                    'recipient_name' => $recipient->name
                ]
            ]);

            // Create transaction for recipient (deposit)
            Transaction::create([
                'user_id' => $recipient->id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'description' => 'Bulk transfer from ' . $sender->name . ' (Ref: ' . $bulkTransfer->reference . ')',
                'status' => 'completed',
                'reference' => 'BTI' . Str::upper(Str::random(10)),
                'metadata' => [
                    'bulk_transfer_id' => $bulkTransfer->id,
                    'sender_id' => $sender->id,
                    'sender_name' => $sender->name
                ]
            ]);

            // Update recipient status to completed
            $transferRecipient->update([
                'status' => 'completed'
            ]);

            // Create notifications
            Notification::create([
                'user_id' => $sender->id,
                'type' => 'transfer.sent',
                'title' => 'Transfer Sent',
                'message' => 'You sent ' . config('app.currency', '₦') . number_format($amount, 2) . ' to ' . $recipient->name,
                'data' => [
                    'amount' => $amount,
                    'recipient_id' => $recipient->id,
                    'recipient_name' => $recipient->name,
                    'bulk_transfer_id' => $bulkTransfer->id
                ]
            ]);

            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'transfer.received',
                'title' => 'Transfer Received',
                'message' => 'You received ' . config('app.currency', '₦') . number_format($amount, 2) . ' from ' . $sender->name,
                'data' => [
                    'amount' => $amount,
                    'sender_id' => $sender->id,
                    'sender_name' => $sender->name,
                    'bulk_transfer_id' => $bulkTransfer->id
                ]
            ]);
        });
    }

    public function getBulkTransferHistory(User $user, $limit = 10)
    {
        return $user->bulkTransfers()
                   ->withCount(['recipients as successful_count' => function($query) {
                       $query->where('status', 'completed');
                   }])
                   ->withCount(['recipients as failed_count' => function($query) {
                       $query->where('status', 'failed');
                   }])
                   ->orderBy('created_at', 'desc')
                   ->paginate($limit);
    }

    public function getBulkTransferDetails($id, User $user)
    {
        return $user->bulkTransfers()
                   ->with(['recipients' => function($query) {
                       $query->with('recipient');
                   }])
                   ->findOrFail($id);
    }
}