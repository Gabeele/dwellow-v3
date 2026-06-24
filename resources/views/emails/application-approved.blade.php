@component('mail::message')
# Good news{{ $firstName ? ', '.$firstName : '' }} — your application is approved

The landlord has approved your application for **{{ $unitLabel }}**@if ($address) at {{ $address }}@endif.

They'll be in touch directly to arrange the next steps, such as the lease and move-in details. There's nothing more you need to do right now.

If you have any questions, just reply to this email.

Thanks,<br>
The Dwellow team
@endcomponent
