@component('mail::panel')
**Order number:** {{ $order->order_number }}

**Subtotal:** {{ $order->currency }} {{ number_format((float) $order->subtotal, 2) }}

**Shipping:** {{ $order->currency }} {{ number_format((float) $order->shipping_amount, 2) }}

**Total:** {{ $order->currency }} {{ number_format((float) $order->total, 2) }}
@endcomponent
