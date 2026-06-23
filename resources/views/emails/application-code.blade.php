@component('mail::message')
# Confirm your email

Use this code to verify your email address and submit your rental application:

@component('mail::panel')
# {{ $code }}
@endcomponent

This code expires in {{ $minutes }} minutes. If you didn't start a Dwellow application, you can safely ignore this email.

Thanks,<br>
The Dwellow team
@endcomponent
