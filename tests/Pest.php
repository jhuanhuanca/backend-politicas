<?php

use App\Domain\Moderation\Services\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

uses(TestCase::class)->in('Unit');

/**
 * @return array<string, string>
 */
function deviceHeaders(string $deviceId = 'device-abc-123'): array
{
    return [
        'X-Device-Fingerprint' => $deviceId,
        'Accept-Language' => 'es',
        'Accept' => 'application/json',
        'User-Agent' => 'Pest',
    ];
}

/**
 * @return array<string, string>
 */
function adminHeaders(): array
{
    return ['X-Admin-Request' => '1'];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function account(array $overrides = []): array
{
    $nick = (string) ($overrides['nick'] ?? 'ana');

    return array_replace([
        'name' => 'Ana Rojas',
        'email' => $nick.'@example.com',
        'nick' => $nick,
        'department' => 'La Paz',
        'birth_year' => 1990,
        'password' => 'clave-segura',
        'password_confirmation' => 'clave-segura',
        'consent_terms' => true,
    ], $overrides);
}

function staffSessionToken(string $email, string $password): string
{
    Carbon::setTestNow(now());

    $pending = test()->withHeaders(adminHeaders())
        ->postJson('/api/admin/auth/login', [
            'email' => $email,
            'password' => $password,
        ])
        ->assertOk()
        ->assertJsonPath('data.pending', 'setup')
        ->assertJsonMissingPath('data.token');

    $token = test()->withHeaders(adminHeaders())
        ->postJson('/api/admin/auth/totp', [
            'challenge' => $pending->json('data.challenge'),
            'code' => app(Totp::class)->code($pending->json('data.secret')),
        ])
        ->assertOk()
        ->json('data.token');

    Carbon::setTestNow();

    return $token;
}
