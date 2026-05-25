<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBureau;
use App\Models\Wilaya;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShippingController extends Controller
{
    public function __construct(
        protected ShippingCalculator $shipping
    ) {}

    public function options(): JsonResponse
    {
        $constantine = config('shipping_constantine.rates.25', ['home' => 520, 'bureau' => 370]);

        return response()->json([
            'origin' => $this->shipping->origin(),
            'provider' => 'ZR Express',
            'currency' => 'DA',
            'local_rates' => [
                'home_fee' => $constantine['home'],
                'bureau_fee' => $constantine['bureau'],
            ],
            'rates' => $this->shipping->allRates(),
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wilaya_id' => ['required', 'integer', 'exists:wilayas,id'],
            'delivery_type' => ['required', 'string', Rule::in(['home', 'bureau'])],
        ]);

        $wilaya = Wilaya::query()->findOrFail($validated['wilaya_id']);

        $quote = $this->shipping->quote($wilaya, $validated['delivery_type']);

        return response()->json([
            'wilaya_id' => $quote['wilaya_id'],
            'wilaya_name' => $quote['wilaya_name'],
            'delivery_type' => $quote['delivery_type'],
            'delivery_label' => $validated['delivery_type'] === 'home'
                ? 'Home delivery'
                : 'ZR Express pickup desk',
            'shipping_fee' => $quote['shipping_fee'],
            'currency' => $quote['currency'],
        ]);
    }

    public function wilayas(): JsonResponse
    {
        return response()->json(
            Wilaya::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'name_ar'])
        );
    }

    public function bureaus(Wilaya $wilaya): JsonResponse
    {
        return response()->json(
            DeliveryBureau::query()
                ->where('wilaya_id', $wilaya->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'wilaya_id', 'name', 'commune', 'address', 'phone'])
        );
    }
}
