<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // The print tees are retired. Fortyfive now starts with sunglasses.
        Product::whereIn('slug', ['college-dropout-tee', 'YE Tee', 'Vultures-tee'])->delete();

        // "Atlas" and 3900 are a working name and price: change them to the real ones.
        Product::whereIn('slug', ['tassili', 'hoggar', 'chrea', 'aures'])->delete();

        $frames = [
            ['Atlas', 'atlas', 3900, ['Black / Smoke', 'Black / Rose', 'Silver / Smoke', 'Black / Tea', 'Clear'], 'A low wraparound frame that sits close and stays put on the move.'],
        ];

        $products = array_map(fn (array $frame) => [
            'name' => $frame[0],
            'slug' => $frame[1],
            'description' => $frame[4],
            'price' => $frame[2],
            'category' => 'Eyewear',
            'sizes' => ['One size'],
            'colors' => $frame[3],
            'images' => [],
            'featured' => true,
            'in_stock' => true,
        ], $frames);

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['slug' => $product['slug']],
                $product
            );
        }
    }
}
