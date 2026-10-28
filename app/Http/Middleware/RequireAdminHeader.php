<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-Admin-Request') !== '1') {
            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'admin' => ['Falta la cabecera de staff.'],
                ],
            ], 401);
        }

        return $next($request);
    }
}
