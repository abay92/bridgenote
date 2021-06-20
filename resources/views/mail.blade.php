@component('mail::message')
# Hallo

{!! $message !!}

Thanks,<br>
{{ config('app.name') }}
@endcomponent