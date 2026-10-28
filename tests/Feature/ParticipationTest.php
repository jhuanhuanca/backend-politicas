<?php

use App\Models\Argument;
use App\Models\ArgumentVote;
use App\Models\LeaderCandidate;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserMetric;

function registerParticipationUser(string $nick, bool $metrics, string $device): User
{
    test()->withHeaders(deviceHeaders($device))->postJson('/api/v1/auth/register', account([
        'nick' => $nick,
        'consent_terms' => true,
        'consent_metrics' => $metrics,
    ]))->assertCreated();

    return User::query()->where('nick', $nick)->firstOrFail();
}

it('requires a session to read participation', function () {
    $this->getJson('/api/v1/me/participation')->assertUnauthorized();
});

it('does not profile an account without metrics consent', function () {
    $user = registerParticipationUser('sin_metricas', false, 'device-sin-metricas');
    UserMetric::query()->create([
        'user_id' => $user->id,
        'debates_count' => 4,
        'participation' => 12,
        'calculated_at' => now(),
    ]);
    LeaderCandidate::query()->create([
        'user_id' => $user->id,
        'participation' => 12,
        'respect' => 100,
        'status' => 'pending',
        'calculated_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/participation', deviceHeaders('device-sin-metricas'))
        ->assertOk()
        ->assertJsonPath('data.consent', false)
        ->assertJsonMissingPath('data.debates')
        ->assertJsonMissingPath('data.candidate')
        ->assertJsonMissingPath('data.user_id');

    expect(UserMetric::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(LeaderCandidate::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('stores participation for the consenting account only', function () {
    $user = registerParticipationUser('voz_activa', true, 'device-voz-activa');
    $other = User::factory()->create(['nick' => 'otra_voz']);

    $topic = Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'Debate de participación.',
        'kind' => 'text',
        'likes_count' => 5,
        'shares_count' => 2,
    ]);
    $argument = Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
        'body' => 'Argumento propio.',
        'likes_count' => 1,
        'favor_count' => 4,
        'contra_count' => 1,
    ]);
    Topic::query()->create([
        'user_id' => $other->id,
        'body' => 'Debate de otra persona.',
        'kind' => 'text',
        'likes_count' => 9,
        'shares_count' => 9,
    ]);
    Reaction::query()->create([
        'user_id' => $user->id,
        'reactable_type' => Topic::class,
        'reactable_id' => $topic->id,
        'kind' => 'like',
    ]);
    ArgumentVote::query()->create([
        'user_id' => $user->id,
        'argument_id' => $argument->id,
        'stance' => 'favor',
    ]);
    $poll = Poll::query()->create([
        'title' => 'Votación de participación.',
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
        'user_id' => $user->id,
        'poll_id' => $poll->id,
        'poll_option_id' => $option->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/participation', deviceHeaders('device-voz-activa'))
        ->assertOk()
        ->assertJsonPath('data.consent', true)
        ->assertJsonPath('data.debates', 1)
        ->assertJsonPath('data.arguments', 1)
        ->assertJsonPath('data.votes', 1)
        ->assertJsonPath('data.argument_votes', 1)
        ->assertJsonPath('data.reactions', 1)
        ->assertJsonPath('data.participation', 9)
        ->assertJsonPath('data.quality', 4)
        ->assertJsonPath('data.respect', 100)
        ->assertJsonPath('data.influence', 8)
        ->assertJsonPath('data.candidate', true)
        ->assertJsonMissingPath('data.user_id')
        ->assertJsonMissingPath('data.nick');

    expect($response->getContent())
        ->not->toContain('voz_activa')
        ->not->toContain('otra_voz')
        ->not->toContain('device_fingerprint');

    $this->assertDatabaseHas('leader_candidates', [
        'user_id' => $user->id,
        'status' => 'pending',
        'participation' => 9,
        'respect' => 100,
    ]);
    $this->assertDatabaseMissing('leader_candidates', [
        'user_id' => $other->id,
    ]);
    $this->assertDatabaseHas('topic_metrics', [
        'topic_id' => $topic->id,
        'arguments_count' => 1,
        'replies_count' => 0,
        'reactions_count' => 1,
        'favor_count' => 4,
        'contra_count' => 1,
        'shares_count' => 2,
    ]);
});

it('removes the leader candidate when open reports lower respect', function () {
    $user = registerParticipationUser('voz_reportada', true, 'device-voz-reportada');
    $reporter = User::factory()->create();
    $topic = Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'Debate con reportes abiertos.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $argument = Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
        'body' => 'Argumento reportado.',
        'likes_count' => 0,
        'favor_count' => 0,
        'contra_count' => 0,
    ]);
    ArgumentVote::query()->create([
        'user_id' => $user->id,
        'argument_id' => $argument->id,
        'stance' => 'favor',
    ]);
    $poll = Poll::query()->create([
        'title' => 'Otra votación.',
        'badge' => 'Abierta',
        'place' => 'Norte',
        'category' => 'politica',
        'position' => 1,
    ]);
    $option = $poll->options()->create([
        'label' => 'Adelante',
        'votes_count' => 1,
        'position' => 1,
    ]);
    PollVote::query()->create([
        'user_id' => $user->id,
        'poll_id' => $poll->id,
        'poll_option_id' => $option->id,
    ]);
    Reaction::query()->create([
        'user_id' => $user->id,
        'reactable_type' => Argument::class,
        'reactable_id' => $argument->id,
        'kind' => 'like',
    ]);
    Report::query()->create([
        'user_id' => $reporter->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $topic->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);
    Report::query()->create([
        'user_id' => $reporter->id,
        'reportable_type' => Argument::class,
        'reportable_id' => $argument->id,
        'reason' => 'insulto',
        'status' => 'open',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/participation', deviceHeaders('device-voz-reportada'))
        ->assertOk()
        ->assertJsonPath('data.participation', 9)
        ->assertJsonPath('data.respect', 60)
        ->assertJsonPath('data.candidate', false)
        ->assertJsonMissingPath('data.reason');

    expect(LeaderCandidate::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('turns the metrics profile on and off for the same account', function () {
    $user = registerParticipationUser('voz_opcional', false, 'device-voz-opcional');
    Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'Debate anterior al consentimiento.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/me/participation/consent', [
            'consent_metrics' => true,
        ], deviceHeaders('device-voz-opcional'))
        ->assertOk()
        ->assertJsonPath('data.consent', true)
        ->assertJsonPath('data.debates', 1)
        ->assertJsonMissingPath('data.user_id');

    expect($user->consents()->where('type', 'metrics')->firstOrFail()->granted_at)->not->toBeNull();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/me/participation/consent', [
            'consent_metrics' => false,
        ], deviceHeaders('device-voz-opcional'))
        ->assertOk()
        ->assertJsonPath('data.consent', false)
        ->assertJsonMissingPath('data.debates');

    expect(UserMetric::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and($user->consents()->where('type', 'metrics')->firstOrFail()->granted_at)->toBeNull();
});
