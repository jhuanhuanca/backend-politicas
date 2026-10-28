<?php

use App\Models\Argument;
use App\Models\ArgumentVote;
use App\Models\LeaderCandidate;
use App\Models\NewsItem;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserMetric;
use Laravel\Sanctum\PersonalAccessToken;

function registerDeletableUser(string $nick, string $device): User
{
    test()->withHeaders(deviceHeaders($device))->postJson('/api/v1/auth/register', account([
        'nick' => $nick,
        'consent_terms' => true,
        'consent_metrics' => true,
    ]))->assertCreated();

    return User::query()->where('nick', $nick)->firstOrFail();
}

it('requires a session to delete an account', function () {
    $this->deleteJson('/api/v1/me', ['nick' => 'alguien'])->assertUnauthorized();
});

it('keeps the account when the confirmation nick does not match', function () {
    $user = registerDeletableUser('voz_queda', 'device-voz-queda');

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/me', ['nick' => 'otro_nick'], deviceHeaders('device-voz-queda'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.nick.0', 'Escribe tu nick para borrar la cuenta.');

    $this->assertDatabaseHas('users', ['id' => $user->id, 'nick' => 'voz_queda']);
});

it('deletes the account and releases the device', function () {
    $user = registerDeletableUser('voz_borra', 'device-voz-borra');
    $other = User::factory()->create(['nick' => 'voz_sigue']);
    $token = $user->createToken('device')->plainTextToken;

    $topic = Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'Debate que se borra con la cuenta.',
        'kind' => 'text',
        'likes_count' => 1,
        'shares_count' => 0,
    ]);
    $argument = Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
        'body' => 'Argumento que se borra con la cuenta.',
        'likes_count' => 0,
        'favor_count' => 0,
        'contra_count' => 0,
    ]);
    $otherTopic = Topic::query()->create([
        'user_id' => $other->id,
        'body' => 'Debate que permanece.',
        'kind' => 'text',
        'likes_count' => 5,
        'shares_count' => 1,
    ]);
    $otherArgument = Argument::query()->create([
        'topic_id' => $otherTopic->id,
        'user_id' => $other->id,
        'body' => 'Argumento que permanece.',
        'likes_count' => 2,
        'favor_count' => 4,
        'contra_count' => 1,
    ]);
    Reaction::query()->create([
        'user_id' => $user->id,
        'reactable_type' => Topic::class,
        'reactable_id' => $otherTopic->id,
        'kind' => 'like',
    ]);
    Reaction::query()->create([
        'user_id' => $other->id,
        'reactable_type' => Topic::class,
        'reactable_id' => $topic->id,
        'kind' => 'like',
    ]);
    ArgumentVote::query()->create([
        'user_id' => $user->id,
        'argument_id' => $otherArgument->id,
        'stance' => 'favor',
    ]);
    $poll = Poll::query()->create([
        'title' => 'Votación que permanece.',
        'badge' => 'Abierta',
        'place' => 'Centro',
        'category' => 'economia',
        'position' => 1,
    ]);
    $option = $poll->options()->create([
        'label' => 'Sí',
        'votes_count' => 3,
        'position' => 1,
    ]);
    PollVote::query()->create([
        'user_id' => $user->id,
        'poll_id' => $poll->id,
        'poll_option_id' => $option->id,
    ]);
    $news = NewsItem::query()->create([
        'title' => 'Noticia ligada al debate.',
        'summary' => 'Sigue publicada.',
        'source' => 'Mesa',
        'topic_id' => $topic->id,
        'published_at' => now(),
    ]);
    Report::query()->create([
        'user_id' => $other->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $topic->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);
    UserMetric::query()->create([
        'user_id' => $user->id,
        'debates_count' => 1,
        'participation' => 3,
        'calculated_at' => now(),
    ]);
    LeaderCandidate::query()->create([
        'user_id' => $user->id,
        'participation' => 8,
        'respect' => 100,
        'status' => 'pending',
        'calculated_at' => now(),
    ]);

    $response = $this->withHeaders(deviceHeaders('device-voz-borra'))
        ->withToken($token)
        ->deleteJson('/api/v1/me', ['nick' => 'voz_borra'])
        ->assertOk()
        ->assertJsonPath('data.deleted', true)
        ->assertJsonMissingPath('data.nick')
        ->assertJsonMissingPath('data.user_id');

    expect($response->getContent())
        ->not->toContain('voz_borra')
        ->not->toContain('device_fingerprint');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('topics', ['id' => $topic->id]);
    $this->assertDatabaseMissing('arguments', ['id' => $argument->id]);
    $this->assertDatabaseMissing('user_metrics', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('leader_candidates', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('reports', ['reportable_id' => $topic->id]);
    $this->assertDatabaseMissing('reactions', ['reactable_id' => $topic->id]);
    $this->assertDatabaseHas('users', ['id' => $other->id, 'nick' => 'voz_sigue']);
    $this->assertDatabaseHas('topics', ['id' => $otherTopic->id, 'likes_count' => 4]);
    $this->assertDatabaseHas('arguments', [
        'id' => $otherArgument->id,
        'favor_count' => 3,
        'likes_count' => 2,
    ]);
    $this->assertDatabaseHas('poll_options', ['id' => $option->id, 'votes_count' => 2]);
    $this->assertDatabaseHas('news_items', ['id' => $news->id, 'topic_id' => null]);
    expect(PersonalAccessToken::query()->where('tokenable_id', $user->id)->exists())->toBeFalse();

    $stale = $this->withHeaders(deviceHeaders('device-voz-borra'))
        ->withToken($token)
        ->getJson('/api/v1/me/participation');

    expect($stale->status())->toBeIn([401, 404])
        ->and($stale->getContent())->not->toContain('consent');

    $this->withHeaders(deviceHeaders('device-voz-borra'))
        ->postJson('/api/v1/auth/register', account([
            'nick' => 'voz_nueva',
            'consent_terms' => true,
            'consent_metrics' => false,
        ]))
        ->assertCreated()
        ->assertJsonPath('data.user.nick', 'voz_nueva');
});
