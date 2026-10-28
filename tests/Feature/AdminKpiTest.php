<?php

use App\Models\Argument;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserMetric;

it('shows staff kpis without the consented profile or its fingerprint', function () {
    Staff::factory()->create([
        'email' => 'kpi@localhost',
        'password' => 'clave-segura',
    ]);

    $author = User::factory()->create(['nick' => 'voz_kpi']);
    $profile = User::factory()->create(['nick' => 'perfil_oculto']);
    $fingerprint = $profile->device_fingerprint;

    $profile->consents()->create([
        'type' => 'metrics',
        'granted_at' => now(),
    ]);
    UserMetric::query()->create([
        'user_id' => $profile->id,
        'debates_count' => 1,
        'arguments_count' => 2,
        'poll_votes_count' => 0,
        'argument_votes_count' => 0,
        'reactions_count' => 1,
        'participation' => 12,
        'quality' => 4,
        'respect' => 80,
        'influence' => 3,
        'calculated_at' => now(),
    ]);

    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'Debate del indicador de la mesa.',
        'kind' => 'text',
        'likes_count' => 1,
        'shares_count' => 2,
    ]);
    $argument = Argument::query()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'body' => 'Un argumento del indicador.',
        'likes_count' => 0,
        'favor_count' => 3,
        'contra_count' => 1,
    ]);
    Reaction::query()->create([
        'user_id' => $author->id,
        'reactable_type' => Topic::class,
        'reactable_id' => $topic->id,
        'kind' => 'like',
    ]);
    Reaction::query()->create([
        'user_id' => $author->id,
        'reactable_type' => Argument::class,
        'reactable_id' => $argument->id,
        'kind' => 'like',
    ]);
    Report::query()->create([
        'user_id' => $author->id,
        'reportable_type' => Topic::class,
        'reportable_id' => $topic->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);

    $token = staffSessionToken('kpi@localhost', 'clave-segura');
    $response = $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/kpis')
        ->assertOk()
        ->assertJsonPath('data.platform.debates', 1)
        ->assertJsonPath('data.platform.arguments', 1)
        ->assertJsonPath('data.platform.voices', 1)
        ->assertJsonPath('data.platform.open_reports', 1)
        ->assertJsonPath('data.platform.consented', 1)
        ->assertJsonPath('data.scores.profiles', 1)
        ->assertJsonPath('data.scores.participation', 12)
        ->assertJsonPath('data.scores.quality', 4)
        ->assertJsonPath('data.scores.respect', 80)
        ->assertJsonPath('data.scores.influence', 3)
        ->assertJsonPath('data.debates.0.author_nick', 'voz_kpi')
        ->assertJsonPath('data.debates.0.arguments', 1)
        ->assertJsonPath('data.debates.0.reactions', 2)
        ->assertJsonPath('data.debates.0.favor', 3)
        ->assertJsonPath('data.debates.0.contra', 1)
        ->assertJsonPath('data.debates.0.shares', 2)
        ->assertJsonMissingPath('data.debates.0.user_id');

    expect($response->getContent())
        ->toContain('Debate del indicador de la mesa.')
        ->not->toContain('perfil_oculto')
        ->not->toContain($fingerprint)
        ->not->toContain('device_fingerprint');
});

it('rejects the kpi dashboard without a staff session', function () {
    $this->withHeaders(adminHeaders())
        ->getJson('/api/admin/kpis')
        ->assertUnauthorized();
});
