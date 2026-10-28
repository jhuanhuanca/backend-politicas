<?php

use App\Models\LeaderCandidate;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('lists consented leader candidates and hides a profile without consent', function () {
    Staff::factory()->create([
        'email' => 'lider@localhost',
        'password' => 'clave-segura',
    ]);

    $voice = User::factory()->create(['nick' => 'voz_lider']);
    $voice->consents()->create([
        'type' => 'metrics',
        'granted_at' => now(),
    ]);
    $pending = LeaderCandidate::query()->create([
        'user_id' => $voice->id,
        'participation' => 9,
        'respect' => 100,
        'status' => 'pending',
        'calculated_at' => now(),
    ]);

    $recognized = User::factory()->create(['nick' => 'voz_reconocida']);
    $recognized->consents()->create([
        'type' => 'metrics',
        'granted_at' => now(),
    ]);
    LeaderCandidate::query()->create([
        'user_id' => $recognized->id,
        'participation' => 20,
        'respect' => 90,
        'status' => 'accepted',
        'calculated_at' => now(),
    ]);

    $hidden = User::factory()->create(['nick' => 'sin_consentimiento']);
    $fingerprint = $hidden->device_fingerprint;
    LeaderCandidate::query()->create([
        'user_id' => $hidden->id,
        'participation' => 30,
        'respect' => 100,
        'status' => 'pending',
        'calculated_at' => now(),
    ]);

    $token = staffSessionToken('lider@localhost', 'clave-segura');
    $response = $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/leaders')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.nick', 'voz_lider')
        ->assertJsonPath('data.0.id', $pending->id)
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonPath('data.0.participation', 9)
        ->assertJsonPath('data.0.respect', 100)
        ->assertJsonPath('data.1.nick', 'voz_reconocida')
        ->assertJsonPath('data.1.status', 'accepted')
        ->assertJsonMissingPath('data.0.user_id');

    expect($response->getContent())
        ->not->toContain('sin_consentimiento')
        ->not->toContain($fingerprint)
        ->not->toContain('device_fingerprint');
});

it('recognizes a candidate and keeps that decision after a new calculation', function () {
    Staff::factory()->create([
        'email' => 'mesa-lider@localhost',
        'password' => 'clave-segura',
    ]);

    $token = staffSessionToken('mesa-lider@localhost', 'clave-segura');

    $this->withHeaders(deviceHeaders('device-voz-mesa'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_mesa',
        'consent_terms' => true,
        'consent_metrics' => true,
    ]))->assertCreated();

    $user = User::query()->where('nick', 'voz_mesa')->firstOrFail();
    foreach (range(1, 3) as $number) {
        Topic::query()->create([
            'user_id' => $user->id,
            'body' => "Debate {$number} de la bandeja.",
            'kind' => 'text',
            'likes_count' => 0,
            'shares_count' => 0,
        ]);
    }

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/participation', deviceHeaders('device-voz-mesa'))
        ->assertOk()
        ->assertJsonPath('data.candidate', true);

    $candidate = LeaderCandidate::query()->where('user_id', $user->id)->firstOrFail();
    Auth::forgetGuards();

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson("/api/admin/leaders/{$candidate->id}/decision", [
        'status' => 'accepted',
    ])->assertOk()
        ->assertJsonPath('data.status', 'accepted')
        ->assertJsonPath('data.nick', 'voz_mesa')
        ->assertJsonMissingPath('data.user_id');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'leader.accepted',
        'subject_type' => 'leader',
        'subject_id' => $candidate->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/participation', deviceHeaders('device-voz-mesa'))
        ->assertOk()
        ->assertJsonPath('data.candidate', true)
        ->assertJsonMissingPath('data.status');

    $this->assertDatabaseHas('leader_candidates', [
        'id' => $candidate->id,
        'status' => 'accepted',
    ]);

    Auth::forgetGuards();

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson("/api/admin/leaders/{$candidate->id}/decision", [
        'status' => 'accepted',
    ])->assertStatus(422)
        ->assertJsonPath('errors.status.0', 'Este candidato ya está en ese estado.');
});

it('rejects the leader inbox without a staff session', function () {
    $this->withHeaders(adminHeaders())
        ->getJson('/api/admin/leaders')
        ->assertUnauthorized();
});
