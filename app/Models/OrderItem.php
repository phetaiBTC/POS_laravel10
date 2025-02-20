<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'quantity',
        'price',
        'subtotal',
        'vendor_id',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function setSubtotalAttribute()
    {
        $this->attributes['subtotal'] = $this->attributes['price'] * $this->attributes['quantity'];
    }
}
