<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wilaya extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_ar',
    ];

    public function deliveryBureaus(): HasMany
    {
        return $this->hasMany(DeliveryBureau::class)->where('is_active', true);
    }

    public function allDeliveryBureaus(): HasMany
    {
        return $this->hasMany(DeliveryBureau::class);
    }
}
