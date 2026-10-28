<?php

namespace App\Http\Middleware;

use App\Models\Sanction;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $sanction = Sanction::query()
                ->where('user_id', $user->id)
                ->whereNull('lifted_at')
                ->where('ends_at', '>', now())
                ->first();

            if ($sanction !== null) {
                throw ValidationException::withMessages([
                    'sanction' => 'Tu cuenta está silenciada hasta el '.$sanction->ends_at->format('d/m/Y').'.',
                ]);
            }
        }

        return $next($request);
    }
}
