<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'compare_price',
        'category',
        'sizes',
        'colors',
        'images',
        'featured',
        'in_stock',
    ];

    protected $casts = [
        'sizes' => 'array',
        'colors' => 'array',
        'images' => 'array',
        'featured' => 'boolean',
        'in_stock' => 'boolean',
        'price' => 'float',
        'compare_price' => 'float',
    ];
}
