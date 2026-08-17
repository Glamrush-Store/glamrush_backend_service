@component('mail::message')
# Payment failed

A payment attempt failed for order **{{ $order->order_number }}**.

@include('emails.partials.order-summary', ['order' => $order])

@component('mail::panel')
**Customer:** {{ $order->shipping_address['full_name'] ?? $order->user?->name ?? 'Guest customer' }}  
**Email:** {{ $order->shipping_address['email'] ?? $order->user?->email ?? 'No email provided' }}  
**Provider:** {{ str_replace('_', ' ', ucfirst($payment->provider)) }}  
**Payment reference:** {{ $payment->reference }}  
**Transaction ID:** {{ $payment->transaction_id ?? 'Not provided' }}  
**Status:** {{ ucfirst($payment->status) }}
@endcomponent

Review the payment and order records before contacting the customer.

@endcomponent
