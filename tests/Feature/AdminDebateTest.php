<?php

use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Carbon;

it('features an older debate and freezes a closed one', function () {
    Staff::factory()->create([
        'email' => 'mesa@localhost',
        'password' => 'clave-segura',
    ]);
    $this->withHeaders(deviceHeaders('cierre-device'))->postJson('/api/v1/auth/register', account([
        'nick' => 'voz_cierre',
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();
    $author = User::query()->where('nick', 'voz_cierre')->firstOrFail();
    $older = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El debate antiguo que la mesa quiere destacar.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
        'created_at' => Carbon::parse('2026-10-01 10:00:00'),
    ]);
    $newer = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El debate nuevo sigue abierto.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
        'created_at' => Carbon::parse('2026-10-14 10:00:00'),
    ]);

    $token = staffSessionToken('mesa@localhost', 'clave-segura');
    $headers = [
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ];

    $listed = $this->withHeaders($headers)
        ->getJson('/api/admin/debates')
        ->assertOk()
        ->assertJsonPath('data.0.author_nick', 'voz_cierre')
        ->assertJsonMissingPath('data.0.user_id');

    expect($listed->getContent())->not->toContain($author->device_fingerprint);

    $this->withHeaders($headers)
        ->postJson("/api/admin/debates/{$older->id}/state", ['action' => 'feature'])
        ->assertOk()
        ->assertJsonPath('data.featured', true);

    $this->getJson('/api/v1/debates')
        ->assertOk()
        ->assertJsonPath('data.0.id', $older->id)
        ->assertJsonPath('data.0.featured', true)
        ->assertJsonPath('data.1.id', $newer->id);

    $this->withHeaders($headers)
        ->postJson("/api/admin/debates/{$newer->id}/state", ['action' => 'feature'])
        ->assertOk();

    $this->assertDatabaseHas('topics', ['id' => $older->id, 'featured_at' => null]);

    $this->withHeaders($headers)
        ->postJson("/api/admin/debates/{$newer->id}/state", ['action' => 'close'])
        ->assertOk()
        ->assertJsonPath('data.closed', true);

    $this->withHeaders(deviceHeaders('cierre-device'))
        ->actingAs($author, 'sanctum')
        ->postJson("/api/v1/debates/{$newer->id}/arguments", [
            'body' => 'Un comentario que no debe entrar.',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.debate.0', 'Este debate está cerrado.');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'debate.closed',
        'subject_id' => $newer->id,
    ]);
});

it('publishes a debate from the staff desk onto the public list', function () {
    Staff::factory()->create([
        'email' => 'redaccion@localhost',
        'password' => 'clave-segura',
    ]);
    $token = staffSessionToken('redaccion@localhost', 'clave-segura');
    $headers = [
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ];

    $created = $this->withHeaders($headers)
        ->postJson('/api/admin/debates', [
            'body' => 'El horario del bus nocturno se discute antes de la votación.',
            'context' => 'Mesa de movilidad',
            'image' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?auto=format&fit=crop&w=1200&q=70',
            'kind' => 'image',
        ])
        ->assertCreated()
        ->assertJsonPath('data.author_nick', 'mesa')
        ->assertJsonMissingPath('data.user_id');

    expect($created->getContent())->not->toContain('device_fingerprint');

    $this->getJson('/api/v1/debates')
        ->assertOk()
        ->assertJsonPath('data.0.body', 'El horario del bus nocturno se discute antes de la votación.')
        ->assertJsonPath('data.0.context', 'Mesa de movilidad')
        ->assertJsonPath('data.0.author.nick', 'mesa')
        ->assertJsonPath('data.0.kind', 'image');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'debate.published',
        'subject_id' => $created->json('data.id'),
    ]);

    $this->withHeaders($headers)
        ->postJson('/api/admin/debates', [
            'body' => 'corto',
            'context' => 'Mesa',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.body.0', 'El debate necesita al menos 8 caracteres.');
});
