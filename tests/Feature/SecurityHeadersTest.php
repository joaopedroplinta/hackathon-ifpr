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

it('sends the CSP as report-only by default, with a nonce that matches the inline script', function () {
    $response = $this->get('/login');

    $csp = $response->headers->get('Content-Security-Policy-Report-Only');
    expect($csp)->toContain("script-src 'self' 'nonce-")->toContain("object-src 'none'");

    preg_match("/'nonce-([^']+)'/", $csp, $m);
    expect($response->getContent())->toContain('nonce="'.$m[1].'"');
    // Anti-framing stays enforced even while the full policy is report-only.
    $response->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");
});

it('enforces the full CSP when the mode says so', function () {
    config(['app.csp_mode' => 'enforce']);

    $response = $this->get('/login');

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'")->toContain("frame-ancestors 'none'");
    expect($response->headers->has('Content-Security-Policy-Report-Only'))->toBeFalse();
});

it('sends HSTS only over HTTPS', function () {
    $this->get('http://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});
