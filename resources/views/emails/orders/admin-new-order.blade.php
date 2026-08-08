@component('mail::message')
# New order received

A new order was placed on {{ config('app.name') }}.

@include('emails.partials.order-summary', ['order' => $order])

@include('emails.partials.order-items', ['order' => $order])

**Customer**

{{ $order->shipping_address['full_name'] ?? $order->user?->name ?? 'Guest customer' }}  
{{ $order->shipping_address['email'] ?? $order->user?->email ?? 'No email provided' }}  
{{ $order->shipping_address['phone'] ?? 'No phone provided' }}

@endcomponent
