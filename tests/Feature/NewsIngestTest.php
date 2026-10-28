<?php

use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('stores rss items linked to the source debate', function () {
    $author = User::factory()->create();
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El horario del bus nocturno está en debate.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    NewsSource::query()->create([
        'name' => 'Mesa de movilidad',
        'feed_url' => 'https://mesa.test/feed.xml',
        'topic_id' => $topic->id,
        'active' => true,
    ]);
    NewsSource::query()->create([
        'name' => 'Fuente apagada',
        'feed_url' => 'https://inactiva.test/feed.xml',
        'active' => false,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://mesa.test/feed.xml' => Http::response(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
              <channel>
                <item>
                  <title>El último bus sigue sin horario.</title>
                  <description>Hay que escribir el horario &lt;strong&gt;antes&lt;/strong&gt; de votar.</description>
                  <guid>bus-1</guid>
                  <pubDate>Wed, 14 Oct 2026 14:30:00 GMT</pubDate>
                </item>
                <item>
                  <title>El turno tarde queda fuera.</title>
                  <description>Quien cierra después de las 21:00 no tiene bus.</description>
                  <guid>bus-2</guid>
                  <pubDate>Wed, 14 Oct 2026 13:00:00 GMT</pubDate>
                </item>
              </channel>
            </rss>
            XML, 200, ['Content-Type' => 'application/rss+xml']),
    ]);

    $this->artisan('news:ingest')
        ->expectsOutputToContain('Se guardaron 2 noticias nuevas.')
        ->assertSuccessful();

    $this->artisan('news:ingest')
        ->expectsOutputToContain('Se guardaron 0 noticias nuevas.')
        ->assertSuccessful();

    expect(NewsItem::query()->count())->toBe(2);

    $this->getJson('/api/v1/news')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'El último bus sigue sin horario.')
        ->assertJsonPath('data.0.summary', 'Hay que escribir el horario antes de votar.')
        ->assertJsonPath('data.0.source', 'Mesa de movilidad')
        ->assertJsonPath('data.0.debate_id', $topic->id)
        ->assertJsonMissingPath('data.0.feed_url')
        ->assertJsonMissingPath('data.0.guid')
        ->assertJsonMissingPath('data.0.user_id');

    expect(NewsSource::query()->where('feed_url', 'https://mesa.test/feed.xml')->firstOrFail()->last_fetched_at)->not->toBeNull();
    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'inactiva.test'));
});

it('skips a feed that is not rss', function () {
    NewsSource::query()->create([
        'name' => 'Fuente rota',
        'feed_url' => 'https://rota.test/feed.xml',
        'active' => true,
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://rota.test/feed.xml' => Http::response('esto no es un feed', 200),
    ]);

    $this->artisan('news:ingest')
        ->expectsOutputToContain('Se guardaron 0 noticias nuevas.')
        ->assertSuccessful();

    expect(NewsItem::query()->count())->toBe(0);
});
