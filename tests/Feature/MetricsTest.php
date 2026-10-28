<?php

use App\Models\Argument;
use App\Models\NewsItem;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Topic;
use App\Models\User;

it('returns public totals without personal data', function () {
    $author = User::factory()->create(['nick' => 'voz_metrica']);
    $voter = User::factory()->create(['nick' => 'voto_metrica']);

    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Un debate para la cifra pública.',
        'kind' => 'image',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'body' => 'Un argumento.',
        'likes_count' => 0,
        'favor_count' => 0,
        'contra_count' => 0,
    ]);
    NewsItem::query()->create([
        'title' => 'Una noticia para la cifra.',
        'summary' => 'Resumen.',
        'source' => 'Mesa',
        'published_at' => now(),
    ]);
    $poll = Poll::query()->create([
        'title' => 'Una votación para la cifra.',
        'badge' => 'Abierta',
        'place' => 'Centro',
        'category' => 'economia',
        'position' => 1,
    ]);
    $option = $poll->options()->create([
        'label' => 'Sí',
        'votes_count' => 1,
        'position' => 1,
    ]);
    PollVote::query()->create([
        'user_id' => $voter->id,
        'poll_id' => $poll->id,
        'poll_option_id' => $option->id,
    ]);

    $response = $this->getJson('/api/v1/metrics')
        ->assertOk()
        ->assertJsonPath('data.debates', 1)
        ->assertJsonPath('data.arguments', 1)
        ->assertJsonPath('data.votes', 1)
        ->assertJsonPath('data.news', 1)
        ->assertJsonPath('data.voices', 2)
        ->assertJsonMissingPath('data.nick')
        ->assertJsonMissingPath('data.user_id');

    expect($response->getContent())
        ->not->toContain('voz_metrica')
        ->not->toContain('voto_metrica')
        ->not->toContain('device_fingerprint');
});
