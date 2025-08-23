<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkTransferRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulk_transfer_id',
        'recipient_id',
        'amount',
        'status',
        'failure_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function bulkTransfer()
    {
        return $this->belongsTo(BulkTransfer::class);
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}