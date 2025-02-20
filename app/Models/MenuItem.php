<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'available',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function menuPrices(): HasMany
    {
        return $this->hasMany(MenuPrice::class);
    }
    public function price()
    {
        return $this->hasOne(MenuPrice::class)
                    ->where('status', true) // ดึงเฉพาะราคาที่ active
                    ->latest(); // ใช้ราคาล่าสุด
    }
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function primaryImage()
{
    return $this->morphOne(Image::class, 'imageable')->latest();
}

}
