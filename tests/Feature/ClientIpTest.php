<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_ip', fn () => response()->json([
        'ip' => request()->ip(),
        'secure' => request()->isSecure(),
    ]));
});

function ipFrom(string $remote, array $headers = []): array
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $remote])
        ->withHeaders($headers)
        ->getJson('/_ip')
        ->json();
}

it('uses the X-Real-IP set by the edge as the client IP', function () {
    expect(ipFrom('100.64.0.5', ['X-Real-IP' => '203.0.113.9'])['ip'])->toBe('203.0.113.9');
});

it('ignores a forged X-Forwarded-For', function () {
    $ip = ipFrom('100.64.0.5', ['X-Real-IP' => '203.0.113.9', 'X-Forwarded-For' => '10.9.8.7']);

    expect($ip['ip'])->toBe('203.0.113.9');
});

it('ignores X-Forwarded-For when there is no X-Real-IP', function () {
    expect(ipFrom('100.64.0.5', ['X-Forwarded-For' => '10.9.8.7'])['ip'])->toBe('100.64.0.5');
});

it('falls back to the peer address when X-Real-IP is not an IP', function () {
    expect(ipFrom('100.64.0.5', ['X-Real-IP' => 'not-an-ip'])['ip'])->toBe('100.64.0.5');
});

it('still honours X-Forwarded-Proto behind the proxy', function () {
    expect(ipFrom('100.64.0.5', ['X-Real-IP' => '203.0.113.9', 'X-Forwarded-Proto' => 'https'])['secure'])->toBeTrue();
});

it('throttles login per real client IP, whatever X-Forwarded-For says', function () {
    $tentar = fn (int $i) => $this->withServerVariables(['REMOTE_ADDR' => '100.64.0.5'])
        ->withHeaders(['X-Real-IP' => '203.0.113.9', 'X-Forwarded-For' => "10.9.8.$i"])
        ->post('/login', ['email' => 'alguem@teste.invalid', 'password' => 'errada']);

    foreach (range(1, 5) as $i) {
        $tentar($i)->assertSessionHasErrors('email');
    }

    $tentar(6)->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Tentativas demais');
});
