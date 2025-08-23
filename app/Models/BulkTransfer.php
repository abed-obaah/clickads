<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'total_amount',
        'recipient_count',
        'status',
        'description',
        'metadata'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipients()
    {
        return $this->hasMany(BulkTransferRecipient::class);
    }

    public function successfulRecipients()
    {
        return $this->recipients()->where('status', 'completed');
    }

    public function failedRecipients()
    {
        return $this->recipients()->where('status', 'failed');
    }

    public function pendingRecipients()
    {
        return $this->recipients()->where('status', 'pending');
    }
}