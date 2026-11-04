@component('mail::message')
# Confirm your subscription

Please confirm that you would like to receive news, offers, and product updates from {{ config('app.name') }}.

@component('mail::button', ['url' => $confirmationUrl])
Confirm subscription
@endcomponent

If you did not request this subscription, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
