<?php

use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('lets two accounts exchange a private message', function () {
    $ana = $this->withHeaders(deviceHeaders('device-ana-chat'))->postJson('/api/v1/auth/register', account([
        'nick' => 'ana_chat',
        'consent_terms' => true,
    ]))->assertCreated();
    $beto = $this->withHeaders(deviceHeaders('device-beto-chat'))->postJson('/api/v1/auth/register', account([
        'nick' => 'beto_chat',
        'consent_terms' => true,
    ]))->assertCreated();

    $anaHeaders = [
        ...deviceHeaders('device-ana-chat'),
        'Authorization' => 'Bearer '.$ana->json('data.token'),
    ];
    $betoHeaders = [
        ...deviceHeaders('device-beto-chat'),
        'Authorization' => 'Bearer '.$beto->json('data.token'),
    ];
    $fingerprint = User::query()->where('nick', 'ana_chat')->value('device_fingerprint');

    $opened = $this->withHeaders($anaHeaders)->postJson('/api/v1/conversations', [
        'nick' => 'beto_chat',
    ])->assertCreated()
        ->assertJsonPath('data.nick', 'beto_chat')
        ->assertJsonMissingPath('data.user_id');

    $conversation = $opened->json('data.id');

    $this->withHeaders($anaHeaders)->postJson("/api/v1/conversations/{$conversation}/messages", [
        'body' => '¿Nos vemos en la mesa?',
    ])->assertCreated()
        ->assertJsonPath('data.body', '¿Nos vemos en la mesa?')
        ->assertJsonPath('data.mine', true)
        ->assertJsonMissingPath('data.sender_id');

    Auth::forgetGuards();
    $inbox = $this->withHeaders($betoHeaders)->getJson('/api/v1/conversations')
        ->assertOk()
        ->assertJsonPath('data.0.nick', 'ana_chat')
        ->assertJsonPath('data.0.last_body', '¿Nos vemos en la mesa?')
        ->assertJsonMissingPath('data.0.low_user_id');

    expect($inbox->getContent())
        ->not->toContain($fingerprint)
        ->not->toContain('device_fingerprint');

    Auth::forgetGuards();
    $this->withHeaders($betoHeaders)->getJson("/api/v1/conversations/{$conversation}/messages?after=0")
        ->assertOk()
        ->assertJsonPath('data.0.body', '¿Nos vemos en la mesa?')
        ->assertJsonPath('data.0.mine', false);

    Auth::forgetGuards();
    $again = $this->withHeaders($betoHeaders)->postJson('/api/v1/conversations', [
        'nick' => 'ana_chat',
    ])->assertCreated();

    expect($again->json('data.id'))->toBe($conversation);
});

it('hides a conversation from anyone else and rejects a missing nick', function () {
    $ana = $this->withHeaders(deviceHeaders('device-ana-privada'))->postJson('/api/v1/auth/register', account([
        'nick' => 'ana_privada',
        'consent_terms' => true,
    ]))->assertCreated();
    $this->withHeaders(deviceHeaders('device-beto-privada'))->postJson('/api/v1/auth/register', account([
        'nick' => 'beto_privada',
        'consent_terms' => true,
    ]))->assertCreated();
    $coro = $this->withHeaders(deviceHeaders('device-coro-privada'))->postJson('/api/v1/auth/register', account([
        'nick' => 'coro_privada',
        'consent_terms' => true,
    ]))->assertCreated();

    $anaHeaders = [
        ...deviceHeaders('device-ana-privada'),
        'Authorization' => 'Bearer '.$ana->json('data.token'),
    ];
    $conversation = $this->withHeaders($anaHeaders)->postJson('/api/v1/conversations', [
        'nick' => 'beto_privada',
    ])->assertCreated()->json('data.id');

    Auth::forgetGuards();
    $this->withHeaders([
        ...deviceHeaders('device-coro-privada'),
        'Authorization' => 'Bearer '.$coro->json('data.token'),
    ])->getJson("/api/v1/conversations/{$conversation}/messages")
        ->assertNotFound()
        ->assertJsonPath('errors.conversation.0', 'No encontramos esa conversación.');

    Auth::forgetGuards();
    $this->withHeaders($anaHeaders)->postJson('/api/v1/conversations', [
        'nick' => 'ana_privada',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.nick.0', 'No puedes conversar contigo.');

    $this->withHeaders($anaHeaders)->postJson('/api/v1/conversations', [
        'nick' => 'nadie_chat',
    ])->assertNotFound()
        ->assertJsonPath('errors.nick.0', 'No encontramos esa cuenta.');
});

it('pages a burst of direct messages', function () {
    $ana = $this->withHeaders(deviceHeaders('device-ana-rafaga'))->postJson('/api/v1/auth/register', account([
        'nick' => 'ana_rafaga',
        'consent_terms' => true,
    ]))->assertCreated();
    $this->withHeaders(deviceHeaders('device-beto-rafaga'))->postJson('/api/v1/auth/register', account([
        'nick' => 'beto_rafaga',
        'consent_terms' => true,
    ]))->assertCreated();

    $headers = [
        ...deviceHeaders('device-ana-rafaga'),
        'Authorization' => 'Bearer '.$ana->json('data.token'),
    ];
    $conversation = $this->withHeaders($headers)->postJson('/api/v1/conversations', [
        'nick' => 'beto_rafaga',
    ])->json('data.id');

    $sender = User::query()->where('nick', 'ana_rafaga')->firstOrFail();
    for ($index = 1; $index <= 31; $index++) {
        DirectMessage::query()->create([
            'conversation_id' => $conversation,
            'sender_id' => $sender->id,
            'body' => "Nota {$index}",
        ]);
    }

    $page = $this->withHeaders($headers)->getJson("/api/v1/conversations/{$conversation}/messages?after=0")
        ->assertOk()
        ->assertJsonCount(30, 'data')
        ->assertJsonPath('meta.more', true);

    $this->withHeaders($headers)->getJson("/api/v1/conversations/{$conversation}/messages?after={$page->json('meta.latest')}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.more', false);
});

it('removes the conversation when an account is deleted', function () {
    $ana = User::factory()->create(['nick' => 'ana_borra']);
    $beto = User::factory()->create(['nick' => 'beto_borra']);
    $conversation = Conversation::query()->create([
        'low_user_id' => min($ana->id, $beto->id),
        'high_user_id' => max($ana->id, $beto->id),
    ]);
    $conversation->messages()->create([
        'sender_id' => $ana->id,
        'body' => 'Hasta luego.',
    ]);

    $ana->delete();

    expect(Conversation::query()->count())->toBe(0);
});
