<?php

use App\Support\TrustedProxies;

it('leaves local requests without a trusted proxy', function () {
    expect(TrustedProxies::resolve(null))->toBeNull()
        ->and(TrustedProxies::resolve(''))->toBeNull()
        ->and(TrustedProxies::resolve('none'))->toBeNull();
});

it('uses the published cloudflare ranges when asked', function () {
    $ranges = TrustedProxies::resolve('cloudflare');

    expect($ranges)->toBeArray()
        ->and($ranges)->toContain('104.16.0.0/13')
        ->and($ranges)->toContain('2606:4700::/32');
});

it('accepts an explicit proxy list', function () {
    expect(TrustedProxies::resolve('203.0.113.5, 203.0.113.6'))->toBe([
        '203.0.113.5',
        '203.0.113.6',
    ]);
});
