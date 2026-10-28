<?php

use App\Models\Poll;
use App\Models\Topic;
use App\Models\User;

function registerAuditUser(string $nick, string $device): User
{
    test()->withHeaders(deviceHeaders($device))->postJson('/api/v1/auth/register', account([
        'nick' => $nick,
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    return User::query()->where('nick', $nick)->firstOrFail();
}

it('keeps the device fingerprint out of the account response', function () {
    $response = $this->withHeaders(deviceHeaders('device-auditor-cuenta'))->postJson('/api/v1/auth/register', account([
        'nick' => 'auditor_cuenta',
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated()
        ->assertJsonPath('data.user.nick', 'auditor_cuenta')
        ->assertJsonMissingPath('data.user.device_fingerprint');

    $hash = User::query()->where('nick', 'auditor_cuenta')->value('device_fingerprint');

    expect($response->getContent())->not->toContain($hash)
        ->and($response->getContent())->not->toContain('device-auditor-cuenta');
});

it('does not publish another person identifiers on public lists', function () {
    $author = registerAuditUser('auditor_publico', 'device-auditor-publico');
    Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'La mesa publica el texto y solo el nick.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $response = $this->getJson('/api/v1/debates')
        ->assertOk()
        ->assertJsonPath('data.0.author.nick', 'auditor_publico')
        ->assertJsonMissingPath('data.0.author.id')
        ->assertJsonMissingPath('data.0.user_id');

    expect($response->getContent())->not->toContain('device_fingerprint')
        ->and($response->getContent())->not->toContain((string) $author->device_fingerprint);

    $this->getJson('/api/v1/metrics')
        ->assertOk()
        ->assertJsonMissingPath('data.user_id');

    expect($this->getJson('/api/v1/metrics')->getContent())->not->toContain('auditor_publico');
});

it('hides likes and votes when the token comes from another device', function () {
    $owner = registerAuditUser('auditor_voto', 'device-auditor-voto');
    $other = registerAuditUser('auditor_otra', 'device-auditor-otra');
    $topic = Topic::query()->create([
        'user_id' => $owner->id,
        'body' => 'El cruce del norte necesita luz antes del voto.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $poll = Poll::query()->create([
        'title' => '¿Dónde va la luz del cruce?',
        'badge' => 'Abierta',
        'place' => 'Norte',
        'category' => 'politica',
        'position' => 1,
    ]);
    $option = $poll->options()->create([
        'label' => 'En la esquina',
        'votes_count' => 0,
        'position' => 1,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/debates/{$topic->id}/reactions", [], deviceHeaders('device-auditor-voto'))
        ->assertOk();
    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/polls/{$poll->id}/votes", [
            'option_id' => $option->id,
        ], deviceHeaders('device-auditor-voto'))
        ->assertOk();

    $ownDebate = $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/debates', deviceHeaders('device-auditor-voto'))
        ->assertOk()
        ->json('data.0');
    $ownPoll = $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/polls', deviceHeaders('device-auditor-voto'))
        ->assertOk()
        ->json('data.0');

    expect($ownDebate['liked'])->toBeTrue()
        ->and($ownPoll['voted_option_id'])->toBe($option->id);

    $foreignDebate = $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/debates', deviceHeaders('device-auditor-otra'))
        ->assertOk()
        ->json('data.0');
    $foreignPoll = $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/polls', deviceHeaders('device-auditor-otra'))
        ->assertOk()
        ->json('data.0');

    expect($foreignDebate['liked'])->toBeFalse()
        ->and($foreignPoll['voted_option_id'])->toBeNull();

    $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/polls', deviceHeaders('device-auditor-otra'))
        ->assertOk()
        ->assertJsonPath('data.0.voted_option_id', null);
});

it('refuses to delete a different account', function () {
    $owner = registerAuditUser('auditor_borra', 'device-auditor-borra');
    registerAuditUser('auditor_sigue', 'device-auditor-sigue');

    $this->actingAs($owner, 'sanctum')
        ->deleteJson('/api/v1/me', [
            'nick' => 'auditor_sigue',
        ], deviceHeaders('device-auditor-borra'))
        ->assertUnprocessable();

    $this->assertDatabaseHas('users', ['nick' => 'auditor_borra']);
    $this->assertDatabaseHas('users', ['nick' => 'auditor_sigue']);
});

it('keeps private routes closed without a session', function () {
    $this->getJson('/api/v1/me/notices')->assertUnauthorized();
    $this->getJson('/api/v1/me/participation')->assertUnauthorized();
    $this->postJson('/api/v1/reports', [
        'target' => 'debate',
        'target_id' => 1,
        'reason' => 'Texto de prueba para el reporte.',
    ])->assertUnauthorized();
    $this->deleteJson('/api/v1/me', ['nick' => 'nadie'])->assertUnauthorized();
    $this->postJson('/api/v1/debates', [
        'body' => 'Un texto que no debe publicarse sin sesión.',
    ])->assertUnauthorized();
});

it('rejects a search made only of wildcards', function () {
    Topic::query()->create([
        'user_id' => User::factory()->create()->id,
        'body' => 'Este texto no debe salir con una búsqueda vacía.',
        'kind' => 'text',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);

    $wildcard = $this->getJson('/api/v1/search?q='.urlencode('%%'))
        ->assertUnprocessable()
        ->assertJsonPath('errors.q.0', 'Escribe al menos 2 caracteres.');

    expect($wildcard->getContent())->not->toContain('Este texto no debe salir');

    $injected = $this->getJson('/api/v1/search?q='.urlencode("%' OR '1'='1"))
        ->assertOk();

    expect($injected->getContent())->not->toContain('Este texto no debe salir');
});

it('slows repeated login attempts', function () {
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->withHeaders(deviceHeaders('device-auditor-rate'))
            ->postJson('/api/v1/auth/login', ['nick' => 'nadie_rate', 'password' => 'clave-segura'])
            ->assertUnauthorized();
    }

    $this->withHeaders(deviceHeaders('device-auditor-rate'))
        ->postJson('/api/v1/auth/login', ['nick' => 'nadie_rate', 'password' => 'clave-segura'])
        ->assertStatus(429);
});
