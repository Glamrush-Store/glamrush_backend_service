@component('mail::message')
# Your order is confirmed

Hi {{ $order->shipping_address['full_name'] ?? $order->user?->name ?? 'there' }},

Your order **{{ $order->order_number }}** has been confirmed with **Pay on Delivery**. No online payment is required now.

@include('emails.partials.order-summary', ['order' => $order])

Please keep the payment ready for collection when your order arrives.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
