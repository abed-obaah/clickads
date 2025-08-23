<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'price',
        'quantity',
        'image',
        'image_mime',
        'is_available'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image && $this->image_mime) {
            return 'data:' . $this->image_mime . ';base64,' . $this->image;
        }
        
        return null;
    }

    public function getFormattedPriceAttribute()
    {
        return config('app.currency', '₦') . number_format($this->price, 2);
    }
}