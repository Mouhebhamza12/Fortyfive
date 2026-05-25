<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'total_amount',
        'subtotal',
        'shipping_fee',
        'payment_status',
        'payment_method',
        'delivery_type',
        'wilaya_id',
        'delivery_bureau_id',
        'bureau_name',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'city',
        'commune',
        'postal_code',
        'items',
        'notes',
    ];

    protected $casts = [
        'items' => 'array',
        'total_amount' => 'float',
        'subtotal' => 'float',
        'shipping_fee' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function deliveryBureau(): BelongsTo
    {
        return $this->belongsTo(DeliveryBureau::class);
    }
}
