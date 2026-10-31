<?php

use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;

it('publishes a news item on the public feed', function () {
    Staff::factory()->create([
        'email' => 'noticias@localhost',
        'password' => 'clave-segura',
    ]);
    $author = User::factory()->create(['nick' => 'voz_noticia']);
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El debate que acompaña la noticia de la mesa.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $token = staffSessionToken('noticias@localhost', 'clave-segura');
    $headers = [
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ];

    $this->withHeaders($headers)
        ->postJson('/api/admin/news', [
            'title' => 'La audiencia abre el presupuesto',
            'summary' => 'La mesa publica el orden del día para comentarlo.',
            'source' => 'Mesa',
            'debate_id' => $topic->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'La audiencia abre el presupuesto')
        ->assertJsonPath('data.source', 'Mesa')
        ->assertJsonPath('data.debate_id', $topic->id)
        ->assertJsonMissingPath('data.guid');

    $this->getJson('/api/v1/news')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'La audiencia abre el presupuesto')
        ->assertJsonPath('data.0.summary', 'La mesa publica el orden del día para comentarlo.')
        ->assertJsonPath('data.0.source', 'Mesa')
        ->assertJsonPath('data.0.debate_id', $topic->id);

    $this->withHeaders($headers)
        ->getJson('/api/admin/news')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'La audiencia abre el presupuesto');

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'news.published',
        'subject_type' => 'news',
    ]);
});

it('rejects a news item without a title', function () {
    Staff::factory()->create([
        'email' => 'noticias-vacias@localhost',
        'password' => 'clave-segura',
    ]);
    $token = staffSessionToken('noticias-vacias@localhost', 'clave-segura');

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/admin/news', [
        'title' => '',
        'summary' => 'Un resumen que sí está escrito.',
        'source' => 'Mesa',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.title.0', 'Escribe el título de la noticia.');
});
