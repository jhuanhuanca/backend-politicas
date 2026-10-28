<?php

use App\Models\Report;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects the report queue without the staff header', function () {
    $this->getJson('/api/admin/reports')->assertUnauthorized()
        ->assertJsonPath('errors.admin.0', 'Falta la cabecera de staff.');
});

it('rejects an expired staff token', function () {
    $staff = Staff::factory()->create();
    $token = $staff->createToken('admin', ['moderation'], now()->subMinute());

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson('/api/admin/me')->assertUnauthorized();
});

it('rejects a public session on the staff queue', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/admin/reports', adminHeaders())
        ->assertUnauthorized()
        ->assertJsonPath('errors.auth.0', 'Esta sesión no es de staff.');
});

it('lists a report for staff and records the decision', function () {
    $staff = Staff::factory()->create([
        'email' => 'mesa@localhost',
        'password' => 'clave-segura',
    ]);
    $author = User::factory()->create(['nick' => 'voz_reporte']);
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El texto reportado del bus nocturno.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $report = Report::query()->create([
        'user_id' => $author->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $topic->id,
        'reason' => 'dato_falso',
        'body' => 'Falta la fuente.',
        'status' => 'open',
    ]);

    $this->withHeaders(adminHeaders())
        ->postJson('/api/admin/auth/login', [
            'email' => 'mesa@localhost',
            'password' => 'incorrecta',
        ])
        ->assertUnauthorized();

    $token = staffSessionToken('mesa@localhost', 'clave-segura');

    $listed = $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/reports')
        ->assertOk()
        ->assertJsonPath('data.0.reporter_nick', 'voz_reporte')
        ->assertJsonPath('data.0.target_type', 'debate')
        ->assertJsonPath('data.0.reason', 'dato_falso')
        ->assertJsonMissingPath('data.0.user_id');

    expect($listed->getContent())->not->toContain('device_fingerprint');

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson("/api/admin/reports/{$report->id}/decision", [
        'status' => 'resolved',
    ])->assertOk()
        ->assertJsonPath('data.status', 'resolved');

    $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'resolved']);
    $this->assertDatabaseHas('admin_audit_logs', [
        'staff_id' => $staff->id,
        'action' => 'report.resolved',
        'subject_id' => $report->id,
    ]);
});
