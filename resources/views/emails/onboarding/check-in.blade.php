@component('mail::message')
# How's it going so far, {{ $firstName }}?

You're one of Dwellow's early landlords, so I'll keep this short: what's working, and what's getting in your way?

Whether you've screened your first applicant or haven't shared a link yet, I'd genuinely like to know what would make this easier. Just hit reply — it comes straight to me.

Thanks for giving Dwellow a try,<br>
Gavin<br>
Dwellow

@component('mail::subcopy')
Don't want these onboarding tips? [Unsubscribe]({{ $unsubscribeUrl }}).
@endcomponent
@endcomponent
