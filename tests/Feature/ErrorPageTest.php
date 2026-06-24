<?php

use Inertia\Testing\AssertableInertia as Assert;

test('a missing page renders the branded Inertia error page', function () {
    $this->get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 404),
        );
});
