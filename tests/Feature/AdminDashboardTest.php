<?php

use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;

it('shows nicks on the staff dashboard and hides the device fingerprint', function () {
    Staff::factory()->create([
        'email' => 'mesa@localhost',
        'password' => 'clave-segura',
    ]);
    $user = User::factory()->create([
        'nick' => 'vecino_panel',
        'name' => 'Vecino Panel',
        'email' => 'vecino_panel@example.com',
        'department' => 'La Paz',
        'municipality' => 'El Alto',
    ]);
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
        ->assertJsonPath('data.users.0.name', 'Vecino Panel')
        ->assertJsonPath('data.users.0.department', 'La Paz')
        ->assertJsonPath('data.users.0.municipality', 'El Alto')
        ->assertJsonPath('data.users.0.metrics_consent', true)
        ->assertJsonPath('data.users.0.device_registered', true)
        ->assertJsonMissingPath('data.users.0.device_fingerprint')
        ->assertJsonMissingPath('data.users.0.email');

    expect($listed->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('PestBrowser')
        ->not->toContain('vecino_panel@example.com');

    User::factory()->create([
        'nick' => 'otra_cuenta',
        'name' => 'Otra Cuenta',
        'department' => 'Pando',
        'municipality' => 'Cobija',
    ]);

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/dashboard?q=Vecino&department=La+Paz&municipality=El+Alto')
        ->assertOk()
        ->assertJsonCount(1, 'data.users')
        ->assertJsonPath('data.users.0.nick', 'vecino_panel');
});
