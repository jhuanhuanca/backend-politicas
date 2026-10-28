<?php

use App\Models\Argument;
use App\Models\NewsItem;
use App\Models\Poll;
use App\Models\Topic;
use App\Models\User;

it('finds debates, news and polls without personal ids', function () {
    $author = User::factory()->create(['nick' => 'voz_busqueda']);
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El horario del bus nocturno tiene que publicarse.',
        'context' => 'Mesa de movilidad',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'body' => 'La parada del mercado cierra después de las 21:00.',
        'likes_count' => 0,
        'favor_count' => 0,
        'contra_count' => 0,
    ]);
    NewsItem::query()->create([
        'title' => 'El último bus sigue sin horario publicado.',
        'summary' => 'La mesa pide el dato antes de votar.',
        'source' => 'Mesa de movilidad',
        'published_at' => now(),
    ]);
    Poll::query()->create([
        'title' => '¿A qué hora cierra el mercado?',
        'badge' => 'Abierta',
        'place' => 'Mercado central',
        'category' => 'economia',
        'position' => 1,
    ]);

    $this->getJson('/api/v1/search?q=horario')
        ->assertOk()
        ->assertJsonPath('data.debates.0.id', $topic->id)
        ->assertJsonPath('data.debates.0.author_nick', 'voz_busqueda')
        ->assertJsonPath('data.news.0.title', 'El último bus sigue sin horario publicado.')
        ->assertJsonMissingPath('data.debates.0.user_id')
        ->assertJsonMissingPath('data.news.0.user_id');

    $this->getJson('/api/v1/search?q=parada')
        ->assertOk()
        ->assertJsonPath('data.debates.0.id', $topic->id)
        ->assertJsonCount(1, 'data.debates');

    $this->getJson('/api/v1/search?q=mercado')
        ->assertOk()
        ->assertJsonPath('data.polls.0.title', '¿A qué hora cierra el mercado?')
        ->assertJsonPath('data.polls.0.place', 'Mercado central')
        ->assertJsonMissingPath('data.polls.0.user_id');
});

it('rejects a search that is too short', function () {
    $this->getJson('/api/v1/search?q=a')
        ->assertUnprocessable()
        ->assertJsonPath('errors.q.0', 'Escribe al menos 2 caracteres.');
});
