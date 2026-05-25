<?php

namespace App\Services;

use App\Models\Wilaya;

class ShippingCalculator
{
    public function origin(): array
    {
        return config('shipping_constantine.origin', [
            'city' => 'Constantine',
            'wilaya_code' => 25,
            'provider' => 'ZR Express',
        ]);
    }

    public function quote(Wilaya|int $wilaya, string $deliveryType): array
    {
        $wilayaModel = $wilaya instanceof Wilaya
            ? $wilaya
            : Wilaya::query()->findOrFail($wilaya);

        $rates = config('shipping_constantine.rates', []);
        $wilayaRates = $rates[$wilayaModel->code] ?? ['home' => 950, 'bureau' => 670];

        $fee = $deliveryType === 'home'
            ? (int) $wilayaRates['home']
            : (int) $wilayaRates['bureau'];

        return [
            'wilaya_id' => $wilayaModel->id,
            'wilaya_code' => $wilayaModel->code,
            'wilaya_name' => $wilayaModel->name,
            'delivery_type' => $deliveryType,
            'shipping_fee' => $fee,
            'currency' => 'DA',
            'origin' => $this->origin(),
            'provider' => 'ZR Express',
        ];
    }

    public function allRates(): array
    {
        $configured = config('shipping_constantine.rates', []);

        return Wilaya::query()
            ->orderBy('code')
            ->get()
            ->map(function (Wilaya $wilaya) use ($configured): array {
                $rates = $configured[$wilaya->code] ?? ['home' => 950, 'bureau' => 670];

                return [
                    'wilaya_id' => $wilaya->id,
                    'wilaya_code' => $wilaya->code,
                    'wilaya_name' => $wilaya->name,
                    'home_fee' => (int) $rates['home'],
                    'bureau_fee' => (int) $rates['bureau'],
                ];
            })
            ->values()
            ->all();
    }
}
