<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user();

        if (! $staff instanceof Staff || $staff->role !== 'moderator' || $staff->totp_confirmed_at === null) {
            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'auth' => ['Esta sesión no es de staff.'],
                ],
            ], 401);
        }

        $token = $staff->currentAccessToken();
        if ($token instanceof PersonalAccessToken && ($token->expires_at === null || $token->expires_at->lte(now()->addMinutes(50)))) {
            $token->forceFill(['expires_at' => now()->addHour()])->save();
        }

        return $next($request);
    }
}
