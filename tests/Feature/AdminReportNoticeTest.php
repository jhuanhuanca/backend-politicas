<?php

use App\Models\Report;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;

it('returns only open reports newer than the cursor', function () {
    Staff::factory()->create([
        'email' => 'avisos@localhost',
        'password' => 'clave-segura',
    ]);
    $author = User::factory()->create([
        'nick' => 'voz_aviso',
        'device_fingerprint' => 'huella-aviso-secreta',
    ]);
    $seenTopic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Un debate que el staff ya vio.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $freshTopic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El texto que acaba de entrar a la cola.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $closedTopic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Un debate cuyo reporte ya se resolvió.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $seen = Report::query()->create([
        'user_id' => $author->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $seenTopic->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);
    $fresh = Report::query()->create([
        'user_id' => $author->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $freshTopic->id,
        'reason' => 'dato_falso',
        'body' => 'Falta la fuente del aviso.',
        'status' => 'open',
    ]);
    Report::query()->create([
        'user_id' => $author->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $closedTopic->id,
        'reason' => 'insulto',
        'status' => 'resolved',
    ]);

    $token = staffSessionToken('avisos@localhost', 'clave-segura');
    $response = $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/reports/newer?after='.$seen->id)
        ->assertOk()
        ->assertJsonPath('data.latest', $fresh->id)
        ->assertJsonCount(1, 'data.reports')
        ->assertJsonPath('data.reports.0.id', $fresh->id)
        ->assertJsonPath('data.reports.0.status', 'open')
        ->assertJsonPath('data.reports.0.reason', 'dato_falso')
        ->assertJsonPath('data.reports.0.reporter_nick', 'voz_aviso')
        ->assertJsonPath('data.reports.0.excerpt', 'El texto que acaba de entrar a la cola.')
        ->assertJsonMissingPath('data.reports.0.user_id');

    expect($response->getContent())
        ->not->toContain('huella-aviso-secreta')
        ->not->toContain('device_fingerprint');
});

it('rejects a newer-report cursor that is not a number', function () {
    Staff::factory()->create([
        'email' => 'avisos-cursor@localhost',
        'password' => 'clave-segura',
    ]);
    $token = staffSessionToken('avisos-cursor@localhost', 'clave-segura');

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/reports/newer?after=no')
        ->assertStatus(422)
        ->assertJsonPath('errors.after.0', 'El cursor del aviso tiene que ser un número.');
});

it('rejects the newer-report feed without a staff session', function () {
    $this->withHeaders(adminHeaders())
        ->getJson('/api/admin/reports/newer')
        ->assertUnauthorized();
});
