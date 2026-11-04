<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\CustomerAddress;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\OrderItem;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    private array $products = [
        [
            'name' => 'Vitamin C Brightening Serum',
            'slug' => 'vitamin-c-brightening-serum',
            'sku' => 'SKN-SRM-001',
            'price' => '7500.00'
        ],
        [
            'name' => 'Matte Finish Foundation',
            'slug' => 'matte-finish-foundation',
            'sku' => 'MKP-FDN-002',
            'price' => '12000.00'
        ],
        [
            'name' => 'Hydrating Face Moisturiser',
            'slug' => 'hydrating-face-moisturiser',
            'sku' => 'SKN-MST-003',
            'price' => '5500.00'
        ],
        ['name' => 'Velvet Lip Rouge', 'slug' => 'velvet-lip-rouge', 'sku' => 'MKP-LIP-004', 'price' => '3500.00'],
        [
            'name' => 'SPF 50 Sunscreen Lotion',
            'slug' => 'spf-50-sunscreen-lotion',
            'sku' => 'SKN-SUN-005',
            'price' => '9000.00'
        ],
        [
            'name' => 'Exfoliating Body Scrub',
            'slug' => 'exfoliating-body-scrub',
            'sku' => 'BDY-SCR-006',
            'price' => '4200.00'
        ],
        [
            'name' => 'Argan Oil Hair Serum',
            'slug' => 'argan-oil-hair-serum',
            'sku' => 'HR-SRM-007',
            'price' => '6800.00'
        ],
    ];

    private array $shippingOptions = [
        ['method' => 'Standard Delivery', 'zone' => 'Lagos Metro', 'amount' => '500.00'],
        ['method' => 'Express Delivery', 'zone' => 'Lagos Metro', 'amount' => '1500.00'],
        ['method' => 'Standard Delivery', 'zone' => 'South West', 'amount' => '1000.00'],
        ['method' => 'Express Delivery', 'zone' => 'South West', 'amount' => '2000.00'],
    ];

    private array $orderStatuses = [
        'pending_payment',
        'pending_on_delivery',
        'paid',
        'processing',
        'shipped',
        'completed',
        'cancelled',
        'failed',
        'refunded'
    ];

    public function run(): void
    {
        $users = User::limit(5)->get();

        if ($users->isEmpty()) {
            return;
        }

        // Assign stable product IDs for the session so snapshots are consistent.
        $products = collect($this->products)->map(fn($p) => array_merge($p, ['id' => (string)Str::ulid()]))->all();

        foreach ($users as $index => $user) {
            $shippingAddress = $this->resolveShippingAddress($user);

            $orders = [
                ['status' => 'shipped', 'days_ago' => 45],
                ['status' => $this->orderStatuses[$index % count($this->orderStatuses)], 'days_ago' => rand(2, 14)],
            ];

            foreach ($orders as $orderMeta) {
                $this->createOrder($user, $products, $shippingAddress, $orderMeta, $index);
            }
        }
    }

    private function resolveShippingAddress(mixed $user): array
    {
        $address = CustomerAddress::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        if ($address) {
            return [
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'phone' => $address->phone,
                'address_line_1' => $address->address_line_1,
                'address_line_2' => $address->address_line_2,
                'country' => $address->country,
                'state' => $address->state,
                'city' => $address->city,
                'postal_code' => $address->postal_code,
            ];
        }

        $nameParts = explode(' ', $user->name, 2);

        return [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'phone' => $user->phone,
            'address_line_1' => '1 Sample Street',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Lagos Island',
            'postal_code' => '100001',
        ];
    }

    private function createOrder(mixed $user, array $products, array $shippingAddress, array $meta, int $index): void
    {
        $shipping = $this->shippingOptions[$index % count($this->shippingOptions)];
        $placedAt = now()->subDays($meta['days_ago']);
        $paidAt = $meta['status'] !== 'pending' ? $placedAt->copy()->addMinutes(rand(5, 30)) : null;

        // Pick 1–3 products and resolve quantities and line totals.
        $pickedProducts = collect($products)->random(rand(1, 3));
        $lineItems = $pickedProducts->map(function (array $product) {
            $quantity = rand(1, 2);
            $lineTotal = round((float)$product['price'] * $quantity, 2);
            return compact('product', 'quantity', 'lineTotal');
        });

        $subtotal = $lineItems->sum('lineTotal');
        $shippingCost = (float)$shipping['amount'];
        $total = $subtotal + $shippingCost;

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . $placedAt->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'status' => $meta['status'],
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'shipping_amount' => number_format($shippingCost, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
            'currency' => 'NGN',
            'shipping_method_name' => $shipping['method'],
            'shipping_zone_name' => $shipping['zone'],
            'shipping_address' => $shippingAddress,
            'placed_at' => $placedAt,
            'paid_at' => $paidAt,
        ]);

        foreach ($lineItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']['id'],
                'product_name' => $item['product']['name'],
                'product_slug' => $item['product']['slug'],
                'sku' => $item['product']['sku'],
                'unit_price' => $item['product']['price'],
                'quantity' => $item['quantity'],
                'line_total' => number_format($item['lineTotal'], 2, '.', ''),
                'product_snapshot' => [
                    'id' => $item['product']['id'],
                    'name' => $item['product']['name'],
                    'slug' => $item['product']['slug'],
                    'sku' => $item['product']['sku'],
                    'price' => $item['product']['price'],
                ],
            ]);
        }
    }
}
