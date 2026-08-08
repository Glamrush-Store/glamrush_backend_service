@component('mail::message')
# We received your order

Hi {{ $order->shipping_address['full_name'] ?? $order->user?->name ?? 'there' }},

Thanks for shopping with {{ config('app.name') }}. Your order has been created and is waiting for payment confirmation.

@include('emails.partials.order-summary', ['order' => $order])

@include('emails.partials.order-items', ['order' => $order])

**Shipping address**

{{ $order->shipping_address['line1'] ?? $order->shipping_address['address_line_1'] ?? '' }}  
{{ $order->shipping_address['line2'] ?? $order->shipping_address['address_line_2'] ?? '' }}  
{{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }}  
{{ $order->shipping_address['country'] ?? '' }}

We will send another email when your payment is confirmed.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
