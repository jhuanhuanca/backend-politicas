<?php

use App\Models\LeaderCandidate;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('shows operational status without job payloads or account fingerprints', function () {
    Staff::factory()->create([
        'email' => 'estado@localhost',
        'password' => 'clave-segura',
    ]);

    $voice = User::factory()->create([
        'nick' => 'voz_oculta',
        'device_fingerprint' => 'huella-estado-secreta',
    ]);
    $voice->consents()->create([
        'type' => 'metrics',
        'granted_at' => now(),
    ]);
    $topic = Topic::query()->create([
        'user_id' => $voice->id,
        'body' => 'Un debate solo para el conteo de estado.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    Report::query()->create([
        'user_id' => $voice->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $topic->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);
    Sanction::query()->create([
        'user_id' => $voice->id,
        'reason' => 'spam',
        'ends_at' => now()->addDay(),
    ]);
    Sanction::query()->create([
        'user_id' => $voice->id,
        'reason' => 'spam',
        'ends_at' => now()->subDay(),
    ]);
    LeaderCandidate::query()->create([
        'user_id' => $voice->id,
        'participation' => 12,
        'respect' => 100,
        'status' => 'pending',
        'calculated_at' => now(),
    ]);
    $hidden = User::factory()->create(['nick' => 'sin_aviso']);
    LeaderCandidate::query()->create([
        'user_id' => $hidden->id,
        'participation' => 20,
        'respect' => 100,
        'status' => 'pending',
        'calculated_at' => now(),
    ]);

    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\Algo', 'secret' => 'nick secreto voz_oculta']),
        'attempts' => 0,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode([
            'displayName' => 'App\\Domain\\Metrics\\Jobs\\RefreshParticipationMetrics',
            'data' => ['command' => 'nick secreto voz_oculta'],
        ]),
        'exception' => 'RuntimeException: nick secreto voz_oculta',
        'failed_at' => now(),
    ]);

    $token = staffSessionToken('estado@localhost', 'clave-segura');
    $response = $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/status')
        ->assertOk()
        ->assertJsonPath('data.database', 'ok')
        ->assertJsonPath('data.queue.driver', 'inmediata')
        ->assertJsonPath('data.queue.pending', 1)
        ->assertJsonPath('data.queue.failed', 1)
        ->assertJsonPath('data.attention.open_reports', 1)
        ->assertJsonPath('data.attention.sanctions', 1)
        ->assertJsonPath('data.attention.leaders', 1)
        ->assertJsonPath('data.failure.name', 'RefreshParticipationMetrics')
        ->assertJsonMissingPath('data.failure.exception');

    expect($response->getContent())
        ->not->toContain('nick secreto')
        ->not->toContain('voz_oculta')
        ->not->toContain('sin_aviso')
        ->not->toContain('huella-estado-secreta')
        ->not->toContain('device_fingerprint');
});

it('rejects the status page without a staff session', function () {
    $this->withHeaders(adminHeaders())
        ->getJson('/api/admin/status')
        ->assertUnauthorized();
});
