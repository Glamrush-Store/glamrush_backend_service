@component('mail::message')
# Payment confirmed

Hi {{ $order->shipping_address['full_name'] ?? $order->user?->name ?? 'there' }},

Your payment for order **{{ $order->order_number }}** has been confirmed. We are getting your order ready.

@include('emails.partials.order-summary', ['order' => $order])

@if ($payment)
@component('mail::panel')
**Payment method:** {{ str_replace('_', ' ', ucfirst($payment->provider)) }}

**Payment reference:** {{ $payment->reference }}

**Transaction ID:** {{ $payment->transaction_id ?? 'N/A' }}
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
