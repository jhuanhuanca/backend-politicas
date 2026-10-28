<?php

use App\Models\AdminAuditLog;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;

it('releases a device so the same nick can enter from another browser', function () {
    Staff::factory()->create([
        'email' => 'recupera@localhost',
        'password' => 'clave-segura',
    ]);

    $registered = $this->withHeaders(deviceHeaders('device-viejo'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_mudanza',
        'consent_terms' => true,
    ]))->assertCreated();

    $oldToken = $registered->json('data.token');
    $user = User::query()->where('nick', 'voz_mudanza')->firstOrFail();
    $fingerprint = $user->device_fingerprint;
    Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'El debate sigue en la misma cuenta.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $token = staffSessionToken('recupera@localhost', 'clave-segura');
    $released = $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/admin/users/voz_mudanza/release-device')
        ->assertOk()
        ->assertJsonPath('data.nick', 'voz_mudanza')
        ->assertJsonPath('data.device_registered', false)
        ->assertJsonMissingPath('data.device_fingerprint')
        ->assertJsonMissingPath('data.user_id');

    expect($released->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('device-viejo');

    expect($user->fresh()->device_fingerprint)->toBeNull()
        ->and($user->devices()->count())->toBe(0)
        ->and($user->tokens()->count())->toBe(0)
        ->and(AdminAuditLog::query()->where('action', 'device.released')->count())->toBe(1);

    $this->withHeaders(deviceHeaders('device-viejo'))
        ->withToken($oldToken)
        ->postJson('/api/v1/auth/logout')
        ->assertUnauthorized();

    $this->withHeaders(deviceHeaders('device-nuevo'))->postJson('/api/v1/auth/login', ['nick' => 'voz_mudanza', 'password' => 'clave-segura'])->assertOk()
        ->assertJsonPath('data.user.nick', 'voz_mudanza')
        ->assertJsonMissingPath('data.user.device_fingerprint');

    $this->withHeaders(deviceHeaders('device-viejo'))->postJson('/api/v1/auth/login', ['nick' => 'voz_mudanza', 'password' => 'clave-segura'])->assertUnauthorized();

    expect($user->fresh()->device_fingerprint)->not->toBe($fingerprint)
        ->and(Topic::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('keeps a waiting account free when the new device already has one', function () {
    $this->withHeaders(deviceHeaders('device-libre-espera'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_espera',
        'consent_terms' => true,
    ]))->assertCreated();
    $this->withHeaders(deviceHeaders('device-ocupado'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_ocupada',
        'consent_terms' => true,
    ]))->assertCreated();

    User::query()->where('nick', 'voz_espera')->update(['device_fingerprint' => null]);
    User::query()->where('nick', 'voz_espera')->firstOrFail()->devices()->delete();

    $this->withHeaders(deviceHeaders('device-ocupado'))->postJson('/api/v1/auth/login', ['nick' => 'voz_espera', 'password' => 'clave-segura'])->assertStatus(409)
        ->assertJsonPath('errors.device.0', 'Este dispositivo ya tiene una cuenta.');

    expect(User::query()->where('nick', 'voz_espera')->value('device_fingerprint'))->toBeNull();
});

it('rejects a second release and a missing nick', function () {
    Staff::factory()->create([
        'email' => 'recupera-dos@localhost',
        'password' => 'clave-segura',
    ]);
    $this->withHeaders(deviceHeaders('device-segunda'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_segunda',
        'consent_terms' => true,
    ]))->assertCreated();

    $token = staffSessionToken('recupera-dos@localhost', 'clave-segura');
    $headers = [
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ];

    $this->withHeaders($headers)
        ->postJson('/api/admin/users/voz_segunda/release-device')
        ->assertOk();

    $this->withHeaders($headers)
        ->postJson('/api/admin/users/voz_segunda/release-device')
        ->assertUnprocessable()
        ->assertJsonPath('errors.device.0', 'Esta cuenta ya está a la espera de un dispositivo.');

    $this->withHeaders($headers)
        ->postJson('/api/admin/users/nadie_aqui/release-device')
        ->assertNotFound()
        ->assertJsonPath('errors.user.0', 'No encontramos esa cuenta.');
});

it('requires a staff session to release a device', function () {
    $this->withHeaders(adminHeaders())
        ->postJson('/api/admin/users/voz_mudanza/release-device')
        ->assertUnauthorized();
});
