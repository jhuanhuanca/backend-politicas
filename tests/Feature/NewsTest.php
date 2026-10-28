<?php

use App\Models\NewsItem;
use App\Models\Topic;
use App\Models\User;

it('lists news linked to a debate without user data', function () {
    $author = User::factory()->create();
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El presupuesto necesita un tope por zona.',
        'kind' => 'image',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    NewsItem::query()->create([
        'title' => 'Diez preguntas para la audiencia del presupuesto.',
        'summary' => 'La audiencia revisa el orden de las obras.',
        'source' => 'Mesa de presupuesto',
        'topic_id' => $topic->id,
        'published_at' => now()->subHours(2),
    ]);

    $this->getJson('/api/v1/news')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Diez preguntas para la audiencia del presupuesto.')
        ->assertJsonPath('data.0.debate_id', $topic->id)
        ->assertJsonPath('data.0.source', 'Mesa de presupuesto')
        ->assertJsonMissingPath('data.0.user_id')
        ->assertJsonMissingPath('data.0.topic');
});

it('shows one news item', function () {
    $item = NewsItem::query()->create([
        'title' => 'Cinco datos del informe de empleo local.',
        'summary' => 'El informe separa empleo formal y cuidados.',
        'source' => 'Observatorio local',
        'published_at' => now()->subHours(3),
    ]);

    $this->getJson("/api/v1/news/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.summary', 'El informe separa empleo formal y cuidados.')
        ->assertJsonPath('data.debate_id', null);
});

it('returns not found when the news item is missing', function () {
    $this->getJson('/api/v1/news/999999')->assertNotFound();
});
