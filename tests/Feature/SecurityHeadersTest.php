<?php

it('sends anti-framing and sniffing headers on public pages', function () {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('keeps the headers on redirects and error pages', function () {
    $this->get('/dashboard')->assertRedirect()->assertHeader('X-Frame-Options', 'DENY');
    $this->get('/nao-existe')->assertNotFound()->assertHeader('X-Frame-Options', 'DENY');
});
