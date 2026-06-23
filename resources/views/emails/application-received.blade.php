@component('mail::message')
# Thanks{{ $firstName ? ', '.$firstName : '' }} — we've received your application

Your application for **{{ $unitLabel }}**@if ($address) at {{ $address }}@endif has been received.

The landlord will review it and be in touch with you by email. There's nothing more you need to do right now.

If you have any questions, just reply to this email.

Thanks,<br>
The Dwellow team
@endcomponent
