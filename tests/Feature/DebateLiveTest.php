<?php

use App\Models\Argument;
use App\Models\Topic;
use App\Models\User;

it('returns new comments and replies without the device fingerprint', function () {
    $author = User::factory()->create(['nick' => 'voz_en_vivo']);
    $fingerprint = $author->device_fingerprint;
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El turno noche necesita una parada en el norte.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $other = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Este debate no tiene que mezclarse.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $comment = Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'body' => 'La parada del norte tiene que quedar.',
    ]);
    $reply = Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'parent_id' => $comment->id,
        'body' => 'Y el horario tiene que decirlo.',
    ]);
    Argument::query()->create([
        'topic_id' => $other->id,
        'user_id' => $author->id,
        'body' => 'Comentario de otro debate.',
    ]);

    $page = $this->getJson("/api/v1/debates/{$topic->id}/updates?after=0")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $comment->id)
        ->assertJsonPath('data.0.parent_id', null)
        ->assertJsonPath('data.1.id', $reply->id)
        ->assertJsonPath('data.1.parent_id', $comment->id)
        ->assertJsonPath('data.1.author.nick', 'voz_en_vivo')
        ->assertJsonPath('meta.more', false)
        ->assertJsonMissingPath('data.0.author.id');

    expect($page->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('device_fingerprint');

    $this->getJson("/api/v1/debates/{$topic->id}/updates?after={$comment->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $reply->id);
});

it('pages a burst of comments instead of returning the whole thread', function () {
    $author = User::factory()->create(['nick' => 'voz_rafaga']);
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Una mesa con muchos comentarios seguidos.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $ids = [];
    for ($index = 1; $index <= 31; $index++) {
        $ids[] = Argument::query()->create([
            'topic_id' => $topic->id,
            'user_id' => $author->id,
            'body' => "Comentario {$index} de la ráfaga.",
        ])->id;
    }

    $page = $this->getJson("/api/v1/debates/{$topic->id}/updates?after=0")
        ->assertOk()
        ->assertJsonCount(30, 'data')
        ->assertJsonPath('meta.more', true)
        ->assertJsonPath('meta.latest', $ids[29]);

    $this->getJson("/api/v1/debates/{$topic->id}/updates?after={$page->json('meta.latest')}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ids[30])
        ->assertJsonPath('meta.more', false);
});

it('returns new debates in short pages and hides the fingerprint', function () {
    $author = User::factory()->create(['nick' => 'voz_publica']);
    $fingerprint = $author->device_fingerprint;
    $ids = [];

    for ($index = 1; $index <= 21; $index++) {
        $ids[] = Topic::query()->create([
            'user_id' => $author->id,
            'body' => "Publicación {$index} que acaba de entrar.",
            'kind' => 'text',
            'likes_count' => 0,
            'shares_count' => 0,
        ])->id;
    }

    $page = $this->getJson('/api/v1/debates/newer?after=0')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('data.0.id', $ids[0])
        ->assertJsonPath('data.0.author.nick', 'voz_publica')
        ->assertJsonPath('data.0.arguments', [])
        ->assertJsonPath('meta.more', true)
        ->assertJsonPath('meta.latest', $ids[19])
        ->assertJsonMissingPath('data.0.author.id');

    expect($page->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('device_fingerprint');

    $this->getJson('/api/v1/debates/newer?after='.$ids[19])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ids[20])
        ->assertJsonPath('meta.more', false);
});

it('returns new comments from every visible debate', function () {
    $author = User::factory()->create(['nick' => 'voz_feed']);
    $fingerprint = $author->device_fingerprint;
    $first = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Primer debate del listado.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $second = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Segundo debate del listado.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $older = Argument::query()->create([
        'topic_id' => $first->id,
        'user_id' => $author->id,
        'body' => 'Comentario que ya estaba.',
    ]);
    $fresh = Argument::query()->create([
        'topic_id' => $second->id,
        'user_id' => $author->id,
        'body' => 'Comentario que acaba de entrar.',
    ]);

    $page = $this->getJson("/api/v1/arguments/newer?after={$older->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $fresh->id)
        ->assertJsonPath('data.0.topic_id', $second->id)
        ->assertJsonPath('data.0.author.nick', 'voz_feed')
        ->assertJsonPath('meta.more', false)
        ->assertJsonMissingPath('data.0.author.id');

    expect($page->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('device_fingerprint');
});

it('rejects a cursor that is not a number', function () {
    $this->getJson('/api/v1/debates/newer?after=no')
        ->assertUnprocessable()
        ->assertJsonPath('errors.after.0', 'El cursor tiene que ser un número.');
});
