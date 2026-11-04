@component('mail::message')
# Welcome to {{ config('app.name') }}

Hi {{ $user->name }},

Your account is ready. You can now save items, manage your addresses, and track your orders from checkout to delivery.

@component('mail::button', ['url' => config('app.url')])
Visit {{ config('app.name') }}
@endcomponent

Thanks for joining us,<br>
{{ config('app.name') }}
@endcomponent
