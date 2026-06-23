@component('mail::message')
# Welcome to dwellow, {{ $name }}

Your email is verified and your account is ready. dwellow helps small landlords screen tenants with confidence — let's get you set up.

@component('mail::button', ['url' => route('dashboard'), 'color' => 'success'])
Go to your dashboard
@endcomponent

If you have any questions, just reply to this email and we'll be happy to help.

Thanks,<br>
The dwellow team
@endcomponent
