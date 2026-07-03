@component('mail::message')
# You're one step from your first applicant, {{ $firstName }}

Here's the one thing standing between you and your first scored applicant:

@component('mail::button', ['url' => $ctaUrl, 'color' => 'success'])
{{ $ctaLabel }}
@endcomponent

{{ $stepDescription }}

Picking a tenant is nerve-wracking. Dwellow gives you one consistent way to compare — so you're deciding on evidence, not a hunch.

Questions? Just reply to this email.

Thanks,<br>
The Dwellow team

@component('mail::subcopy')
Don't want these onboarding tips? [Unsubscribe]({{ $unsubscribeUrl }}).
@endcomponent
@endcomponent
