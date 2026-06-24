@component('mail::message')
# An update on your application{{ $firstName ? ', '.$firstName : '' }}

Thank you for applying for **{{ $unitLabel }}**@if ($address) at {{ $address }}@endif.

After careful consideration, the landlord has decided not to move forward with your application at this time. We know this isn't the news you were hoping for, and we wish you the best of luck with your search.

If you have any questions, just reply to this email.

Thanks,<br>
The Dwellow team
@endcomponent
