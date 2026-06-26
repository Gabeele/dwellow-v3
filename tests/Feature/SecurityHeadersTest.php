<?php

test('baseline security headers are present on web responses', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('hsts header is omitted over plain http', function () {
    $response = $this->get(route('home'));

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});
