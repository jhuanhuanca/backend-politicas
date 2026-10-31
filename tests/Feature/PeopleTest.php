<?php

use App\Models\User;

it('suggests other registered nicks and hides private fields', function () {
    $headers = deviceHeaders('device-ana-gente');
    $token = $this->withHeaders($headers)->postJson('/api/v1/auth/register', account([
        'nick' => 'ana',
        'consent_terms' => true,
    ]))->json('data.token');

    $this->withHeaders(deviceHeaders('device-beto-gente'))->postJson('/api/v1/auth/register', account([
        'nick' => 'beto',
        'name' => 'Beto Quispe',
        'consent_terms' => true,
    ]))->assertCreated();

    User::factory()->create([
        'nick' => 'mesa',
        'name' => 'Mesa',
        'email' => 'mesa@example.com',
    ]);

    $listed = $this->withHeaders([...$headers, 'Authorization' => 'Bearer '.$token])
        ->getJson('/api/v1/people')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nick', 'beto')
        ->assertJsonMissingPath('data.0.email')
        ->assertJsonMissingPath('data.0.name')
        ->assertJsonMissingPath('data.0.department')
        ->assertJsonMissingPath('data.0.municipality')
        ->assertJsonMissingPath('data.0.device_fingerprint');

    expect($listed->getContent())->not->toContain('Beto Quispe')->not->toContain('mesa@example.com');

    $this->withHeaders([...$headers, 'Authorization' => 'Bearer '.$token])
        ->getJson('/api/v1/people?q=bet')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nick', 'beto');

    $this->withHeaders([...$headers, 'Authorization' => 'Bearer '.$token])
        ->getJson('/api/v1/people?q=ana')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires a session to list people', function () {
    $this->getJson('/api/v1/people')->assertUnauthorized();
});
