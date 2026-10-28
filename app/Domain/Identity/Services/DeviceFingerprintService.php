<?php

namespace App\Domain\Identity\Services;

use Illuminate\Http\Request;

class DeviceFingerprintService
{
    public function generate(Request $request): string
    {
        $serverSignals = implode('|', [
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language'),
            (string) $request->header('Sec-CH-UA-Platform'),
            (string) $request->ip(),
        ]);

        $clientHash = (string) $request->header('X-Device-Fingerprint', '');

        return hash('sha256', $serverSignals.'|'.$clientHash);
    }
}
