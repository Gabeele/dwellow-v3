@component('mail::message')
# Welcome to Dwellow

Thanks for signing up. Before you can get started, please confirm your email address so we know it's really you.

@component('mail::button', ['url' => $url, 'color' => 'success'])
Verify Email Address
@endcomponent

This link expires shortly for your security. If you did not create a Dwellow account, no further action is required.

Thanks,<br>
The Dwellow team

@component('mail::subcopy')
If you're having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your web browser:

[{{ $url }}]({{ $url }})
@endcomponent
@endcomponent
