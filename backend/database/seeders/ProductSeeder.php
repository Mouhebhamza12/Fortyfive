<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'College Dropout Tee',
                'slug' => 'college-dropout-tee',
                'description' => 'Built for everyday wear with a straightforward fit, heavier hand feel, and clean front print.',
                'price' => 3500,
                'category' => 'T-Shirts',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => ['White', 'Black'],
                'images' => [],
                'featured' => true,
                'in_stock' => true,
            ],
            [
                'name' => 'Ye Tee',
                'slug' => 'YE Tee',
                'description' => 'A clean everyday tee with a centered graphic, structured collar, and enough weight to hold its shape.',
                'price' => 3500,
                'category' => 'T-Shirts',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => ['White', 'Black'],
                'images' => [],
                'featured' => true,
                'in_stock' => true,
            ],
            [
                'name' => 'Vultures Tee',
                'slug' => 'Vultures-tee',
                'description' => 'A direct graphic tee with a clean cut, balanced proportions, and a heavier cotton base.',
                'price' => 3500,
                'category' => 'T-Shirts',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => ['White', 'Black'],
                'images' => [],
                'featured' => false,
                'in_stock' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['slug' => $product['slug']],
                $product
            );
        }
    }
}
