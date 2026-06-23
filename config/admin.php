<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Allowlist
    |--------------------------------------------------------------------------
    |
    | The Filament admin panel is for developers and founders only — there is
    | no public registration. Only users whose email appears in this list may
    | access it. Set ADMIN_EMAILS to a comma-separated list of addresses.
    |
    */

    'emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

];
