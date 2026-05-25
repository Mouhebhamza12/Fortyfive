<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBureau;
use App\Models\Order;
use App\Models\Wilaya;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        protected ShippingCalculator $shipping
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_type' => ['required', 'string', Rule::in(['home', 'bureau'])],
            'wilaya_id' => ['required', 'integer', 'exists:wilayas,id'],
            'delivery_bureau_id' => ['nullable', 'integer', 'exists:delivery_bureaus,id'],
            'commune' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.slug' => ['required', 'string', 'max:255'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.size' => ['nullable', 'string', 'max:20'],
            'items.*.color' => ['nullable', 'string', 'max:50'],
            'items.*.image' => ['nullable', 'string', 'max:500'],
        ]);

        $wilaya = Wilaya::query()->findOrFail($validated['wilaya_id']);

        if ($validated['delivery_type'] === 'home') {
            $request->validate([
                'commune' => ['required', 'string', 'max:255'],
                'shipping_address' => ['required', 'string', 'max:1000'],
            ]);
        } else {
            $request->validate([
                'delivery_bureau_id' => ['required', 'integer', 'exists:delivery_bureaus,id'],
            ]);

            $bureau = DeliveryBureau::query()
                ->where('wilaya_id', $wilaya->id)
                ->where('is_active', true)
                ->findOrFail($validated['delivery_bureau_id']);
        }

        $items = collect($validated['items'])->map(function (array $item): array {
            return [
                'slug' => $item['slug'],
                'name' => $item['name'],
                'price' => (float) $item['price'],
                'quantity' => (int) $item['quantity'],
                'size' => $item['size'] ?? null,
                'color' => $item['color'] ?? null,
                'image' => $item['image'] ?? null,
            ];
        })->values()->all();

        $subtotal = collect($items)->sum(
            fn (array $item): float => $item['price'] * $item['quantity']
        );

        $shippingQuote = $this->shipping->quote($wilaya, $validated['delivery_type']);
        $shippingFee = (float) $shippingQuote['shipping_fee'];

        $user = $request->user();

        $orderData = [
            'user_id' => $user?->id,
            'order_number' => $this->generateOrderNumber(),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total_amount' => $subtotal + $shippingFee,
            'payment_status' => 'unpaid',
            'payment_method' => 'cash_on_delivery',
            'delivery_type' => $validated['delivery_type'],
            'wilaya_id' => $wilaya->id,
            'customer_name' => trim($validated['customer_name']),
            'customer_email' => isset($validated['customer_email']) && $validated['customer_email'] !== ''
                ? Str::lower(trim($validated['customer_email']))
                : ($user?->email ?: $this->guestEmailFromPhone(trim($validated['customer_phone']))),
            'customer_phone' => trim($validated['customer_phone']),
            'city' => $wilaya->name,
            'postal_code' => 'N/A',
            'items' => $items,
            'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
        ];

        if ($validated['delivery_type'] === 'home') {
            $orderData['commune'] = trim($validated['commune']);
            $orderData['shipping_address'] = trim($validated['shipping_address']);
            $orderData['delivery_bureau_id'] = null;
            $orderData['bureau_name'] = null;
        } else {
            $orderData['delivery_bureau_id'] = $bureau->id;
            $orderData['bureau_name'] = $bureau->name;
            $orderData['commune'] = $bureau->commune;
            $orderData['shipping_address'] = $bureau->address;
        }

        $order = Order::create($orderData);

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $this->serializeOrder($order->load(['wilaya', 'deliveryBureau'])),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['wilaya', 'deliveryBureau'])
            ->latest()
            ->get()
            ->map(fn (Order $order) => $this->serializeOrder($order));

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($this->serializeOrder($order->load(['wilaya', 'deliveryBureau'])));
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'YE-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    protected function guestEmailFromPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: 'guest';

        return "guest-{$digits}@orders.local";
    }

    protected function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'delivery_type' => $order->delivery_type,
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'total_amount' => $order->total_amount,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'shipping_address' => $order->shipping_address,
            'city' => $order->city,
            'commune' => $order->commune,
            'wilaya' => $order->wilaya?->only(['id', 'code', 'name']),
            'bureau_name' => $order->bureau_name,
            'delivery_bureau' => $order->deliveryBureau?->only(['id', 'name', 'address', 'commune']),
            'items' => $order->items,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }
}
