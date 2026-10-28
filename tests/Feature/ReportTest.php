<?php

use App\Models\NewsItem;
use App\Models\Topic;
use App\Models\User;

function registerReporter(string $nick): User
{
    test()->withHeaders(deviceHeaders())->postJson('/api/v1/auth/register', account([
        'nick' => $nick,
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    return User::query()->where('nick', $nick)->firstOrFail();
}

it('stores a report without exposing the reporter', function () {
    $user = registerReporter('reporte_voz');
    $topic = Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'Este argumento se puede reportar.',
        'kind' => 'image',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $this->postJson('/api/v1/reports', [
        'target_type' => 'debate',
        'target_id' => $topic->id,
        'reason' => 'dato_falso',
    ])->assertUnauthorized();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reports', [
            'target_type' => 'debate',
            'target_id' => $topic->id,
            'reason' => 'dato_falso',
            'body' => 'La cifra del horario no coincide con el cartel.',
        ], deviceHeaders())
        ->assertCreated()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.reason', 'dato_falso')
        ->assertJsonMissingPath('data.user_id');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reports', [
            'target_type' => 'debate',
            'target_id' => $topic->id,
            'reason' => 'spam',
        ], deviceHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('errors.target_id.0', 'Ya enviaste un reporte de esto.');
});

it('rejects a report for a missing news item', function () {
    $user = registerReporter('reporte_noticia');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reports', [
            'target_type' => 'news',
            'target_id' => 999999,
            'reason' => 'spam',
        ], deviceHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('errors.target_id.0', 'No encontramos eso para reportar.');

    $item = NewsItem::query()->create([
        'title' => 'Nota para reportar.',
        'summary' => 'Resumen breve.',
        'source' => 'Mesa',
        'published_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reports', [
            'target_type' => 'news',
            'target_id' => $item->id,
            'reason' => 'fuera_de_tema',
        ], deviceHeaders())
        ->assertCreated()
        ->assertJsonPath('data.reason', 'fuera_de_tema');
});
