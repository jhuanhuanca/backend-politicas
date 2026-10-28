<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Services\DeviceFingerprintService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleDevice
{
    public function __construct(private readonly DeviceFingerprintService $fingerprints) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $current = $this->fingerprints->generate($request);

        $known = $user->device_fingerprint;

        if (! is_string($known) || $known === '' || ! hash_equals($known, $current)) {
            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'device' => ['Este dispositivo no coincide con la cuenta.'],
                ],
            ], 401);
        }

        return $next($request);
    }
}
