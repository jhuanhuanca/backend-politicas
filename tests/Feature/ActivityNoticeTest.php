<?php

use App\Models\ActivityNotice;
use App\Models\Argument;
use App\Models\Topic;
use App\Models\User;

function registerNoticeUser(string $nick, string $device): User
{
    test()->withHeaders(deviceHeaders($device))->postJson('/api/v1/auth/register', account([
        'nick' => $nick,
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    return User::query()->where('nick', $nick)->firstOrFail();
}

it('requires a session to read notices', function () {
    $this->getJson('/api/v1/me/notices')->assertUnauthorized();
});

it('notifies the debate author and keeps the notice private', function () {
    $owner = registerNoticeUser('duena_aviso', 'device-duena-aviso');
    $voice = registerNoticeUser('voz_aviso', 'device-voz-aviso');
    $topic = Topic::query()->create([
        'user_id' => $owner->id,
        'body' => 'El bus de la noche necesita horario publicado.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/debates/{$topic->id}/arguments", [
            'body' => 'Lo escribo yo misma para la mesa.',
        ], deviceHeaders('device-duena-aviso'))
        ->assertCreated();

    expect(ActivityNotice::query()->count())->toBe(0);

    $this->actingAs($voice, 'sanctum')
        ->postJson("/api/v1/debates/{$topic->id}/arguments", [
            'body' => 'El horario de las 21:00 tiene que quedar en la parada.',
        ], deviceHeaders('device-voz-aviso'))
        ->assertCreated();

    $this->actingAs($voice, 'sanctum')
        ->getJson('/api/v1/me/notices', deviceHeaders('device-voz-aviso'))
        ->assertOk()
        ->assertJsonPath('meta.unread', 0)
        ->assertJsonCount(0, 'data');

    $listed = $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/me/notices', deviceHeaders('device-duena-aviso'))
        ->assertOk()
        ->assertJsonPath('meta.unread', 1)
        ->assertJsonPath('data.0.kind', 'argument')
        ->assertJsonPath('data.0.author_nick', 'voz_aviso')
        ->assertJsonPath('data.0.debate_id', $topic->id)
        ->assertJsonPath('data.0.read', false)
        ->assertJsonMissingPath('data.0.user_id');

    $noticeId = $listed->json('data.0.id');

    $this->actingAs($voice, 'sanctum')
        ->postJson("/api/v1/me/notices/{$noticeId}/read", [], deviceHeaders('device-voz-aviso'))
        ->assertNotFound();

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/me/notices/{$noticeId}/read", [], deviceHeaders('device-duena-aviso'))
        ->assertOk()
        ->assertJsonPath('data.read', true)
        ->assertJsonPath('meta.unread', 0);

    $owner->delete();

    $this->assertDatabaseMissing('activity_notices', ['id' => $noticeId]);
});

it('notifies the argument author when someone replies', function () {
    $owner = registerNoticeUser('duena_respuesta', 'device-duena-respuesta');
    $voice = registerNoticeUser('voz_respuesta', 'device-voz-respuesta');
    $topic = Topic::query()->create([
        'user_id' => $owner->id,
        'body' => 'La ciclovía no reemplaza el último bus.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $comment = Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $owner->id,
        'body' => 'Son dos necesidades distintas.',
        'likes_count' => 0,
        'favor_count' => 0,
        'contra_count' => 0,
    ]);

    $this->actingAs($voice, 'sanctum')
        ->postJson("/api/v1/debates/{$topic->id}/arguments", [
            'body' => 'Las dos tienen que quedar escritas.',
            'parent_id' => $comment->id,
        ], deviceHeaders('device-voz-respuesta'))
        ->assertCreated();

    $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/me/notices', deviceHeaders('device-duena-respuesta'))
        ->assertOk()
        ->assertJsonPath('meta.unread', 1)
        ->assertJsonPath('data.0.kind', 'reply')
        ->assertJsonPath('data.0.author_nick', 'voz_respuesta')
        ->assertJsonMissingPath('data.0.user_id');
});
