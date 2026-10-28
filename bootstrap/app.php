<?php

use App\Domain\Identity\Exceptions\DeviceAlreadyRegisteredException;
use App\Domain\Identity\Exceptions\InvalidCredentialsException;
use App\Domain\Moderation\Exceptions\StaffCredentialsException;
use App\Domain\Moderation\Exceptions\StaffTotpException;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureAccountOpen;
use App\Http\Middleware\EnsureSingleDevice;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\RequireAdminHeader;
use App\Support\TrustedProxies;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'device' => EnsureSingleDevice::class,
            'open' => EnsureAccountOpen::class,
            'admin.header' => RequireAdminHeader::class,
            'admin' => EnsureStaff::class,
        ]);

        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: RequireAdminHeader::class,
        );

        $middleware->api(prepend: [
            AssignRequestId::class,
        ]);

        $trustedProxies = TrustedProxies::resolve(env('TRUSTED_PROXIES'));

        if ($trustedProxies !== null) {
            $middleware->trustProxies(at: $trustedProxies);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (DeviceAlreadyRegisteredException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'device' => ['Este dispositivo ya tiene una cuenta.'],
                ],
            ], 409);
        });

        $exceptions->render(function (InvalidCredentialsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'auth' => ['Nick o dispositivo incorrectos.'],
                ],
            ], 401);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'auth' => ['No autenticado.'],
                ],
            ], 401);
        });

        $exceptions->render(function (StaffTotpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = $exception->notice === 'expirado'
                ? 'La verificación expiró. Vuelve a ingresar la clave.'
                : 'El código no coincide.';

            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'auth' => [$message],
                ],
            ], 401);
        });

        $exceptions->render(function (StaffCredentialsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'auth' => ['Correo o clave incorrectos.'],
                ],
            ], 401);
        });

        $exceptions->dontReport([
            DeviceAlreadyRegisteredException::class,
            InvalidCredentialsException::class,
            StaffCredentialsException::class,
            StaffTotpException::class,
        ]);

        $exceptions->report(function (Throwable $exception): void {
            $request = request();

            if ($request === null || ! $request->is('api/*')) {
                return;
            }

            Log::error('api.failure', [
                'request_id' => (string) $request->attributes->get('request_id', ''),
                'method' => $request->method(),
                'path' => $request->path(),
                'exception' => $exception::class,
            ]);
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*') || $exception instanceof HttpExceptionInterface) {
                return null;
            }

            return response()->json([
                'data' => (object) [],
                'meta' => [
                    'request_id' => (string) $request->attributes->get('request_id', ''),
                ],
                'errors' => [
                    'server' => ['No pudimos completar eso.'],
                ],
            ], 500);
        });
    })->create();
