@component('mail::message')
# Your first applicant is scored, {{ $firstName }}

{{ $applicantName }} applied for **{{ $unitLabel }}** — Dwellow has read their documents, contacted their references, and scored them against the criteria you set.

@component('mail::button', ['url' => route('applications.index'), 'color' => 'success'])
Compare applicants
@endcomponent

You'll see the Score with the evidence behind it — income, rental history, references — so you can see *why*, not just the number. You make the call; we make sure it's an informed one.

The Dwellow team

@component('mail::subcopy')
Don't want these onboarding tips? [Unsubscribe]({{ $unsubscribeUrl }}).
@endcomponent
@endcomponent
