<?php

use App\Domain\Moderation\Services\Totp;

it('matches the standard authenticator sample', function () {
    $totp = new Totp;
    $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    expect($totp->code($secret, 59))->toBe('287082');
    expect($totp->match($secret, '287082', null, 59))->toBe(1);
    expect($totp->match($secret, '287082', 1, 59))->toBeNull();
});
