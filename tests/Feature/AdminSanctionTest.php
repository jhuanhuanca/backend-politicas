<?php

use App\Models\NewsItem;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('silences the reported author and lets staff lift it', function () {
    Staff::factory()->create([
        'email' => 'sancion@localhost',
        'password' => 'clave-segura',
    ]);
    $token = staffSessionToken('sancion@localhost', 'clave-segura');

    $this->withHeaders(deviceHeaders('device-silencio'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_silencio',
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    $author = User::query()->where('nick', 'voz_silencio')->firstOrFail();
    $fingerprint = $author->device_fingerprint;
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Un debate que el staff va a silenciar.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $reporter = User::factory()->create(['nick' => 'voz_que_reporta']);
    $report = Report::query()->create([
        'user_id' => $reporter->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $topic->id,
        'reason' => 'insulto',
        'status' => 'open',
    ]);

    $created = $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson("/api/admin/reports/{$report->id}/sanction", [
        'days' => 7,
    ])->assertOk()
        ->assertJsonPath('data.nick', 'voz_silencio')
        ->assertJsonPath('data.reason', 'insulto')
        ->assertJsonPath('data.lifted', false)
        ->assertJsonMissingPath('data.user_id');

    expect($created->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('device_fingerprint')
        ->not->toContain('voz_que_reporta');

    $sanctionId = $created->json('data.id');
    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'sanction.applied',
        'subject_type' => 'sanction',
        'subject_id' => $sanctionId,
    ]);

    $blocked = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/debates', [
            'body' => 'Esto no debería publicarse durante el silencio.',
        ], deviceHeaders('device-silencio'))
        ->assertStatus(422);

    expect($blocked->json('errors.sanction.0'))->toContain('silenciada');

    Auth::forgetGuards();

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson("/api/admin/reports/{$report->id}/sanction", [
        'days' => 1,
    ])->assertStatus(422)
        ->assertJsonPath('errors.report.0', 'Esta cuenta ya está silenciada.');

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson("/api/admin/sanctions/{$sanctionId}/lift")
        ->assertOk()
        ->assertJsonPath('data.lifted', true)
        ->assertJsonPath('data.nick', 'voz_silencio');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'sanction.lifted',
        'subject_id' => $sanctionId,
    ]);

    $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/debates', [
            'body' => 'Después de levantar el silencio ya puede publicar.',
        ], deviceHeaders('device-silencio'))
        ->assertCreated();
});

it('does not silence a news report', function () {
    Staff::factory()->create([
        'email' => 'sancion-noticia@localhost',
        'password' => 'clave-segura',
    ]);
    $reporter = User::factory()->create();
    $news = NewsItem::query()->create([
        'title' => 'Una noticia sin cuenta.',
        'summary' => 'Resumen.',
        'source' => 'Mesa',
        'published_at' => now(),
    ]);
    $report = Report::query()->create([
        'user_id' => $reporter->id,
        'reportable_type' => NewsItem::class,
        'reportable_id' => $news->id,
        'reason' => 'dato_falso',
        'status' => 'open',
    ]);
    $token = staffSessionToken('sancion-noticia@localhost', 'clave-segura');

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson("/api/admin/reports/{$report->id}/sanction", [
        'days' => 7,
    ])->assertStatus(422)
        ->assertJsonPath('errors.report.0', 'Esta noticia no tiene una cuenta para silenciar.');
});

it('allows publishing when the silence already ended', function () {
    $this->withHeaders(deviceHeaders('device-plazo'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_plazo',
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();
    $author = User::query()->where('nick', 'voz_plazo')->firstOrFail();
    Sanction::query()->create([
        'user_id' => $author->id,
        'reason' => 'spam',
        'ends_at' => now()->subDay(),
    ]);

    $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/debates', [
            'body' => 'El plazo del silencio ya pasó.',
        ], deviceHeaders('device-plazo'))
        ->assertCreated();
});

it('rejects the sanction list without a staff session', function () {
    $this->withHeaders(adminHeaders())
        ->getJson('/api/admin/sanctions')
        ->assertUnauthorized();
});
