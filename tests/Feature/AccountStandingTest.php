<?php

use App\Models\Sanction;
use App\Models\User;

it('tells a silenced account the date and hides the fingerprint', function () {
    $this->withHeaders(deviceHeaders('device-silencio-cuenta'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_silencio_cuenta',
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    $user = User::query()->where('nick', 'voz_silencio_cuenta')->firstOrFail();
    $fingerprint = $user->device_fingerprint;
    Sanction::query()->create([
        'user_id' => $user->id,
        'reason' => 'insulto',
        'ends_at' => now()->addDays(7),
    ]);
    Sanction::query()->create([
        'user_id' => $user->id,
        'reason' => 'spam',
        'ends_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/standing', deviceHeaders('device-silencio-cuenta'))
        ->assertOk()
        ->assertJsonPath('data.silenced', true)
        ->assertJsonPath('data.until', now()->addDays(7)->format('d/m/Y'))
        ->assertJsonMissingPath('data.user_id')
        ->assertJsonMissingPath('data.nick')
        ->assertJsonMissingPath('data.reason');

    expect($response->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('device_fingerprint')
        ->not->toContain('insulto');
});

it('reports an open account when the silence was lifted', function () {
    $this->withHeaders(deviceHeaders('device-libre'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_libre',
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    $user = User::query()->where('nick', 'voz_libre')->firstOrFail();
    Sanction::query()->create([
        'user_id' => $user->id,
        'reason' => 'spam',
        'ends_at' => now()->addDay(),
        'lifted_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/standing', deviceHeaders('device-libre'))
        ->assertOk()
        ->assertJsonPath('data.silenced', false)
        ->assertJsonPath('data.until', null);
});

it('requires a session to read the account standing', function () {
    $this->getJson('/api/v1/me/standing')->assertUnauthorized();
});
