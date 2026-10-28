<?php

use App\Models\Poll;
use App\Models\User;

function registerPollUser(string $nick): User
{
    test()->withHeaders(deviceHeaders())->postJson('/api/v1/auth/register', account([
        'nick' => $nick,
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    return User::query()->where('nick', $nick)->firstOrFail();
}

it('lists polls without exposing the voter', function () {
    $poll = Poll::query()->create([
        'title' => '¿Qué obra va primero?',
        'badge' => 'Abierta',
        'place' => 'Centro',
        'category' => 'economia',
        'position' => 1,
    ]);
    $poll->options()->create([
        'label' => 'Agua en los barrios altos',
        'votes_count' => 4,
        'position' => 1,
    ]);

    $this->getJson('/api/v1/polls')
        ->assertOk()
        ->assertJsonPath('data.0.title', '¿Qué obra va primero?')
        ->assertJsonPath('data.0.options.0.votes', 4)
        ->assertJsonPath('data.0.voted_option_id', null)
        ->assertJsonMissingPath('data.0.user_id')
        ->assertJsonMissingPath('data.0.options.0.user_id');
});

it('records one vote per account', function () {
    $user = registerPollUser('voto_obra');
    $poll = Poll::query()->create([
        'title' => '¿Dónde va el cuidado nocturno?',
        'badge' => 'En vivo',
        'place' => 'Norte',
        'category' => 'cuidados',
        'position' => 1,
    ]);
    $market = $poll->options()->create([
        'label' => 'Junto al mercado',
        'votes_count' => 2,
        'position' => 1,
    ]);
    $school = $poll->options()->create([
        'label' => 'En la escuela',
        'votes_count' => 1,
        'position' => 2,
    ]);

    $this->postJson("/api/v1/polls/{$poll->id}/votes", [
        'option_id' => $market->id,
    ])->assertUnauthorized();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/polls/{$poll->id}/votes", [
            'option_id' => $market->id,
        ], deviceHeaders())
        ->assertOk()
        ->assertJsonPath('data.voted_option_id', $market->id)
        ->assertJsonPath('data.options.0.votes', 3)
        ->assertJsonMissingPath('data.user_id');

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/polls/{$poll->id}/votes", [
            'option_id' => $school->id,
        ], deviceHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('errors.option_id.0', 'Ya registraste tu voto en esta propuesta.');
});

it('rejects an option from another poll', function () {
    $user = registerPollUser('voto_ajeno');
    $poll = Poll::query()->create([
        'title' => 'Horario del mercado',
        'badge' => 'Cierra hoy',
        'place' => 'Mercado central',
        'category' => 'economia',
        'position' => 1,
    ]);
    $other = Poll::query()->create([
        'title' => 'Otra votación',
        'badge' => 'Abierta',
        'place' => 'Centro',
        'category' => 'cultura',
        'position' => 2,
    ]);
    $foreign = $other->options()->create([
        'label' => 'Opción ajena',
        'votes_count' => 0,
        'position' => 1,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/polls/{$poll->id}/votes", [
            'option_id' => $foreign->id,
        ], deviceHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('errors.option_id.0', 'Esa opción no pertenece a esta votación.');
});
