<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'otp',
        'otp_expires_at',
        'is_verified',
        'balance'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'is_verified' => 'boolean',
        'balance' => 'decimal:2'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Add stores relationship
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

     // Add bank details relationship
    public function bankDetails()
    {
        return $this->hasMany(BankDetail::class);
    }

    public function defaultBankDetail()
    {
        return $this->hasOne(BankDetail::class)->where('is_default', true);
    }


     // Add bulk transfers relationship
    public function bulkTransfers()
    {
        return $this->hasMany(BulkTransfer::class);
    }

    public function bulkTransferRecipients()
    {
        return $this->hasMany(BulkTransferRecipient::class, 'recipient_id');
    }
}