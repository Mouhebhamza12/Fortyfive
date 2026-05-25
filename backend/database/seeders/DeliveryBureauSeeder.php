<?php

namespace Database\Seeders;

use App\Models\DeliveryBureau;
use App\Models\Wilaya;
use Illuminate\Database\Seeder;

class DeliveryBureauSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, list<array{name: string, commune: string, address: string, phone?: string}>> $catalog */
        $catalog = require database_path('data/zr_express_bureaus.php');
        $seededIds = [];

        Wilaya::query()->orderBy('code')->each(function (Wilaya $wilaya) use ($catalog, &$seededIds): void {
            $entries = $catalog[$wilaya->code] ?? [[
                'name' => "ZR Express {$wilaya->name}",
                'commune' => $wilaya->name,
                'address' => "Stop desk ZR Express, {$wilaya->name}",
            ]];

            foreach ($entries as $entry) {
                $bureau = DeliveryBureau::updateOrCreate(
                    [
                        'wilaya_id' => $wilaya->id,
                        'name' => $entry['name'],
                    ],
                    [
                        'commune' => $entry['commune'],
                        'address' => $entry['address'],
                        'phone' => $entry['phone'] ?? null,
                        'is_active' => true,
                    ]
                );

                $seededIds[] = $bureau->id;
            }
        });

        DeliveryBureau::query()
            ->whereNotIn('id', $seededIds)
            ->delete();
    }
}
