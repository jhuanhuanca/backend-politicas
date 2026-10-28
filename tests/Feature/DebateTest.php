<?php

use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function registerDebateUser(string $nick): User
{
    test()->withHeaders(deviceHeaders())->postJson('/api/v1/auth/register', account([
        'nick' => $nick,
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    return User::query()->where('nick', $nick)->firstOrFail();
}

it('lists debates without exposing the device fingerprint', function () {
    $author = User::factory()->create(['nick' => 'clara_rivas']);

    Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El transporte nocturno tiene que llegar al norte.',
        'context' => 'Mesa de movilidad',
        'kind' => 'image',
        'likes_count' => 4,
        'shares_count' => 1,
    ]);

    $this->getJson('/api/v1/debates')
        ->assertOk()
        ->assertJsonPath('data.0.body', 'El transporte nocturno tiene que llegar al norte.')
        ->assertJsonPath('data.0.author.nick', 'clara_rivas')
        ->assertJsonPath('data.0.likes', 4)
        ->assertJsonMissingPath('data.0.author.device_fingerprint');
});

it('requires a session to publish a debate', function () {
    $this->postJson('/api/v1/debates', [
        'body' => 'Un argumento que necesita cuenta.',
    ])->assertUnauthorized();
});

it('publishes a debate and a comment for the same device', function () {
    $user = registerDebateUser('nora_debate');

    $created = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/debates', [
            'body' => 'El turno noche también necesita parada en el norte.',
        ], deviceHeaders())
        ->assertCreated()
        ->assertJsonPath('data.author.nick', $user->nick)
        ->assertJsonPath('data.body', 'El turno noche también necesita parada en el norte.');

    $topicId = $created->json('data.id');

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/debates/{$topicId}/arguments", [
            'body' => 'De acuerdo con la parada.',
        ], deviceHeaders())
        ->assertCreated()
        ->assertJsonPath('data.body', 'De acuerdo con la parada.');
});

it('toggles a like on and off', function () {
    $user = registerDebateUser('luis_debate');
    $topic = Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'Hay que separar el agua de los eventos.',
        'kind' => 'image',
        'likes_count' => 2,
        'shares_count' => 0,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/debates/{$topic->id}/reactions", [], deviceHeaders())
        ->assertOk()
        ->assertJsonPath('data.liked', true)
        ->assertJsonPath('data.likes', 3);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/debates/{$topic->id}/reactions", [], deviceHeaders())
        ->assertOk()
        ->assertJsonPath('data.liked', false)
        ->assertJsonPath('data.likes', 2);
});

it('shows a debate with its replies', function () {
    $author = User::factory()->create(['nick' => 'elena_cruz']);
    $topic = Topic::query()->create([
        'user_id' => $author->id,
        'body' => 'El horario del bus tiene que publicarse en la parada.',
        'context' => 'Mesa de movilidad',
        'kind' => 'image',
        'likes_count' => 1,
        'shares_count' => 0,
    ]);
    $comment = $topic->arguments()->create([
        'user_id' => $author->id,
        'body' => 'El último bus pasa a las 20:10.',
        'likes_count' => 2,
    ]);
    $comment->replies()->create([
        'topic_id' => $topic->id,
        'user_id' => $author->id,
        'body' => 'Y el mapa dice otra hora.',
        'likes_count' => 1,
    ]);

    $this->getJson("/api/v1/debates/{$topic->id}")
        ->assertOk()
        ->assertJsonPath('data.body', 'El horario del bus tiene que publicarse en la parada.')
        ->assertJsonPath('data.arguments.0.body', 'El último bus pasa a las 20:10.')
        ->assertJsonPath('data.arguments.0.replies.0.body', 'Y el mapa dice otra hora.')
        ->assertJsonMissingPath('data.author.device_fingerprint')
        ->assertJsonMissingPath('data.arguments.0.author.id');
});

it('adds a reply under the main comment', function () {
    $user = registerDebateUser('andres_hilo');
    $topic = Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'La ciclovía no reemplaza el bus de noche.',
        'kind' => 'image',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $comment = $topic->arguments()->create([
        'user_id' => $user->id,
        'body' => 'Son dos necesidades distintas.',
        'likes_count' => 0,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/debates/{$topic->id}/arguments", [
            'body' => 'Las dos tienen que quedar escritas.',
            'parent_id' => $comment->id,
        ], deviceHeaders())
        ->assertCreated()
        ->assertJsonPath('data.body', 'Las dos tienen que quedar escritas.')
        ->assertJsonPath('data.author.nick', $user->nick);

    $this->assertDatabaseHas('arguments', [
        'parent_id' => $comment->id,
        'body' => 'Las dos tienen que quedar escritas.',
    ]);
});

it('rejects a reply to another reply', function () {
    $user = registerDebateUser('marta_hilo');
    $topic = Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'El agua no puede competir con un evento.',
        'kind' => 'image',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $comment = $topic->arguments()->create([
        'user_id' => $user->id,
        'body' => 'Hay que separar lo urgente.',
        'likes_count' => 0,
    ]);
    $reply = $comment->replies()->create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
        'body' => 'De acuerdo con separarlo.',
        'likes_count' => 0,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/debates/{$topic->id}/arguments", [
            'body' => 'Esto ya es una respuesta.',
            'parent_id' => $reply->id,
        ], deviceHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('errors.parent_id.0', 'Responde al comentario principal del hilo.');
});

it('casts a vote and can withdraw it', function () {
    $user = registerDebateUser('voto_mesa');
    $topic = Topic::query()->create([
        'user_id' => $user->id,
        'body' => 'El tope por zona tiene que votarse en el argumento.',
        'kind' => 'image',
        'likes_count' => 0,
        'shares_count' => 0,
    ]);
    $comment = $topic->arguments()->create([
        'user_id' => $user->id,
        'body' => 'A favor del tope.',
        'likes_count' => 0,
    ]);

    $this->postJson("/api/v1/arguments/{$comment->id}/votes", [
        'stance' => 'favor',
    ])->assertUnauthorized();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/arguments/{$comment->id}/votes", [
            'stance' => 'favor',
        ], deviceHeaders())
        ->assertOk()
        ->assertJsonPath('data.favor', 1)
        ->assertJsonPath('data.contra', 0)
        ->assertJsonPath('data.stance', 'favor');

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/arguments/{$comment->id}/votes", [
            'stance' => 'contra',
        ], deviceHeaders())
        ->assertOk()
        ->assertJsonPath('data.favor', 0)
        ->assertJsonPath('data.contra', 1)
        ->assertJsonPath('data.stance', 'contra');

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/arguments/{$comment->id}/votes", [
            'stance' => 'contra',
        ], deviceHeaders())
        ->assertOk()
        ->assertJsonPath('data.favor', 0)
        ->assertJsonPath('data.contra', 0)
        ->assertJsonPath('data.stance', null);

    $this->getJson("/api/v1/debates/{$topic->id}")
        ->assertOk()
        ->assertJsonPath('data.arguments.0.favor', 0)
        ->assertJsonPath('data.arguments.0.stance', null)
        ->assertJsonMissingPath('data.arguments.0.votes');
});

it('rejects a debate from another device', function () {
    $user = registerDebateUser('rosa_debate');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/debates', [
            'body' => 'Este dispositivo no es el de la cuenta.',
        ], deviceHeaders('otro-dispositivo'))
        ->assertUnauthorized()
        ->assertJsonPath('errors.device.0', 'Este dispositivo no coincide con la cuenta.');
});

it('publishes a photo from the device and a video from an https link', function () {
    $user = registerDebateUser('foto_debate');

    $uploaded = $this->actingAs($user, 'sanctum')
        ->withHeaders(deviceHeaders())
        ->post('/api/v1/debates', [
            'body' => 'La foto del mercado a la hora de cierre.',
            'kind' => 'image',
            'media' => UploadedFile::fake()->image('mercado.jpg'),
        ])
        ->assertCreated()
        ->assertJsonPath('data.kind', 'image');

    $image = (string) $uploaded->json('data.image');
    expect($image)->toStartWith('/media/debates/')
        ->and($image)->toEndWith('.jpg')
        ->and(is_file(public_path(ltrim($image, '/'))))->toBeTrue();
    unlink(public_path(ltrim($image, '/')));

    $this->actingAs($user, 'sanctum')
        ->withHeaders(deviceHeaders())
        ->postJson('/api/v1/debates', [
            'body' => 'El video de la audiencia queda enlazado.',
            'kind' => 'video',
            'image' => 'https://example.com/audiencia.mp4',
        ])
        ->assertCreated()
        ->assertJsonPath('data.kind', 'video')
        ->assertJsonPath('data.image', 'https://example.com/audiencia.mp4');

    $this->actingAs($user, 'sanctum')
        ->withHeaders(deviceHeaders())
        ->post('/api/v1/debates', [
            'body' => 'Un archivo que no es una imagen.',
            'kind' => 'image',
            'media' => UploadedFile::fake()->create('nota.txt', 10, 'text/plain'),
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.media.0', 'La imagen tiene que ser JPG, PNG, WEBP o GIF.');

    $this->actingAs($user, 'sanctum')
        ->withHeaders(deviceHeaders())
        ->postJson('/api/v1/debates', [
            'body' => 'Un enlace sin cifrado no se guarda.',
            'kind' => 'image',
            'image' => 'http://example.com/foto.jpg',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.image.0', 'El enlace tiene que empezar por https://.');
});
