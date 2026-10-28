<?php

use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;

it('shows nicks on the staff dashboard and hides the device fingerprint', function () {
    Staff::factory()->create([
        'email' => 'mesa@localhost',
        'password' => 'clave-segura',
    ]);
    $user = User::factory()->create(['nick' => 'vecino_panel']);
    $fingerprint = $user->device_fingerprint;
    $user->devices()->create([
        'fingerprint' => $fingerprint,
        'user_agent' => 'PestBrowser',
        'last_seen_at' => now(),
    ]);
    $user->consents()->create([
        'type' => 'metrics',
        'granted_at' => now(),
    ]);
    Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'Un debate visible solo como conteo.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $token = staffSessionToken('mesa@localhost', 'clave-segura');
    $listed = $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('data.counts.users', 1)
        ->assertJsonPath('data.counts.debates', 1)
        ->assertJsonPath('data.users.0.nick', 'vecino_panel')
        ->assertJsonPath('data.users.0.metrics_consent', true)
        ->assertJsonPath('data.users.0.device_registered', true)
        ->assertJsonMissingPath('data.users.0.device_fingerprint');

    expect($listed->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('PestBrowser');
});
