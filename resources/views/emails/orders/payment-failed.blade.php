@component('mail::message')
# Payment was not completed

Hi {{ $order->shipping_address['full_name'] ?? $order->user?->name ?? 'there' }},

We could not confirm payment for order **{{ $order->order_number }}**.

@include('emails.partials.order-summary', ['order' => $order])

@component('mail::panel')
**Payment method:** {{ str_replace('_', ' ', ucfirst($payment->provider)) }}

**Payment reference:** {{ $payment->reference }}

**Status:** {{ ucfirst($payment->status) }}
@endcomponent

You can return to checkout and try payment again while the order is still available.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
