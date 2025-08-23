<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bank_name',
        'account_name',
        'account_number',
        'bank_code',
        'currency',
        'is_default',
        'is_verified'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include default bank details.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include verified bank details.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}