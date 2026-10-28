<?php

namespace App\Domain\Identity\Services;

use App\Models\User;
use Illuminate\Http\Request;

class ResolvePublicViewer
{
    public function __construct(private readonly DeviceFingerprintService $fingerprints) {}

    public function from(Request $request): ?User
    {
        $user = $request->user('sanctum');

        if (! $user instanceof User) {
            return null;
        }

        $current = $this->fingerprints->generate($request);

        if (! hash_equals((string) $user->device_fingerprint, $current)) {
            return null;
        }

        return $user;
    }
}
