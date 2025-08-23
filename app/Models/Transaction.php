<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
        'status',
        'reference',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Add this method to ensure proper type validation
    public static function getValidTypes()
    {
        return ['deposit', 'withdrawal', 'transfer', 'payment', 'transfer_out', 'transfer_in'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}