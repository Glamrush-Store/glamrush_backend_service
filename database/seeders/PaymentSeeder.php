<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Infrastructure\Persistence\Eloquent\Models\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Models\PaymentTransaction;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $methods = PaymentMethod::query()
            ->whereIn('code', ['paystack', 'flutterwave'])
            ->get()
            ->keyBy('code');

        if ($methods->isEmpty()) {
            return;
        }

        $orders = Order::query()
            ->orderBy('created_at')
            ->limit(4)
            ->get();

        foreach ($orders as $index => $order) {
            $provider = $index % 2 === 0 ? 'paystack' : 'flutterwave';
            $method = $methods->get($provider);

            if ($method === null) {
                continue;
            }

            $isPaid = $order->paid_at !== null || in_array($order->status, ['paid', 'processing', 'shipped', 'completed', 'delivered', 'confirmed'], true);
            $reference = 'SEED-' . strtoupper($provider) . '-' . $order->order_number;
            $transactionId = $isPaid ? (string) crc32($reference) : null;

            $payment = Payment::updateOrCreate(
                ['reference' => $reference],
                [
                    'order_id' => $order->id,
                    'payment_method_id' => $method->id,
                    'provider' => $provider,
                    'provider_reference' => $isPaid ? $provider . '-' . $order->id : null,
                    'transaction_id' => $transactionId,
                    'amount' => $order->total,
                    'currency' => $order->currency,
                    'status' => $isPaid ? 'paid' : 'pending',
                    'authorization_url' => $isPaid ? null : "https://checkout.example.test/{$provider}/{$reference}",
                    'paid_at' => $isPaid ? ($order->paid_at ?? $order->updated_at) : null,
                    'failed_at' => null,
                    'metadata' => [
                        'seeded' => true,
                        'order_number' => $order->order_number,
                    ],
                ]
            );

            PaymentTransaction::updateOrCreate(
                [
                    'payment_id' => $payment->id,
                    'type' => $isPaid ? 'paid' : 'initialize',
                ],
                [
                    'status' => $payment->status,
                    'provider_reference' => $payment->provider_reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payload' => [
                        'seeded' => true,
                        'provider' => $provider,
                        'reference' => $reference,
                        'transaction_id' => $transactionId,
                    ],
                ]
            );
        }
    }
}
