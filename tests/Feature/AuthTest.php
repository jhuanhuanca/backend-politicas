<?php

use App\Mail\WelcomeAccountMail;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;

it('registers a nick for a device', function () {
    Mail::fake();

    $response = $this->withHeaders(deviceHeaders())
        ->postJson('/api/v1/auth/register', account([
            'nick' => 'ana',
            'consent_terms' => true,
            'consent_metrics' => true,
        ]));

    $response->assertCreated()
        ->assertJsonPath('data.user.nick', 'ana')
        ->assertJsonMissingPath('data.user.device_fingerprint');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('users', ['nick' => 'ana']);
    $this->assertDatabaseHas('consents', [
        'type' => 'terms',
    ]);
    $this->assertDatabaseHas('consents', [
        'type' => 'metrics',
    ]);

    Mail::assertQueued(WelcomeAccountMail::class, function (WelcomeAccountMail $mail): bool {
        return $mail->hasTo('ana@example.com') && $mail->nick === 'ana';
    });

    expect($response->json('data.user'))->not->toHaveKey('email')
        ->and($response->getContent())->not->toContain('ana@example.com');
});

it('rejects an account for someone under 18', function () {
    $this->withHeaders(deviceHeaders())
        ->postJson('/api/v1/auth/register', account([
            'nick' => 'menor',
            'birth_year' => now()->year - 17,
        ]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.birth_year.0', 'Tienes que tener al menos 18 años para crear una cuenta.');
});

it('rejects a second account from the same device', function () {
    $headers = deviceHeaders();

    $this->withHeaders($headers)->postJson('/api/v1/auth/register', account([
        'nick' => 'ana',
        'consent_terms' => true,
    ]))->assertCreated();

    $this->withHeaders($headers)->postJson('/api/v1/auth/register', account([
        'nick' => 'beto',
        'consent_terms' => true,
    ]))->assertStatus(409)
        ->assertJsonPath('errors.device.0', 'Este dispositivo ya tiene una cuenta.');
});

it('rejects an invalid nick', function () {
    $this->withHeaders(deviceHeaders())
        ->postJson('/api/v1/auth/register', account([
            'nick' => 'a',
            'consent_terms' => true,
        ]))
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['nick']]);
});

it('logs in with the same device', function () {
    $headers = deviceHeaders();

    $this->withHeaders($headers)->postJson('/api/v1/auth/register', account([
        'nick' => 'ana',
        'consent_terms' => true,
    ]))->assertCreated();

    $this->withHeaders($headers)->postJson('/api/v1/auth/login', ['nick' => 'ana', 'password' => 'clave-segura'])->assertOk()
        ->assertJsonPath('data.user.nick', 'ana');
});

it('rejects login from another device', function () {
    $this->withHeaders(deviceHeaders('device-1'))->postJson('/api/v1/auth/register', account([
        'nick' => 'ana',
        'consent_terms' => true,
    ]))->assertCreated();

    $this->withHeaders(deviceHeaders('device-2'))->postJson('/api/v1/auth/login', ['nick' => 'ana', 'password' => 'clave-segura'])->assertUnauthorized();
});

it('logs out and revokes the token', function () {
    $headers = deviceHeaders();

    $token = $this->withHeaders($headers)->postJson('/api/v1/auth/register', account([
        'nick' => 'ana',
        'consent_terms' => true,
    ]))->json('data.token');

    $this->withHeaders($headers)
        ->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('blocks logout when the device fingerprint changes', function () {
    $token = $this->withHeaders(deviceHeaders('device-1'))->postJson('/api/v1/auth/register', account([
        'nick' => 'ana',
        'consent_terms' => true,
    ]))->json('data.token');

    $this->withHeaders(deviceHeaders('device-2'))
        ->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertUnauthorized();

    expect(PersonalAccessToken::query()->count())->toBe(1);
});
