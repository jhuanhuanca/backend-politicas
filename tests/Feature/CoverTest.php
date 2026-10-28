<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

it('saves a cover photo under media', function () {
    test()->withHeaders(deviceHeaders())->postJson('/api/v1/auth/register', account([
        'nick' => 'portada_cuenta',
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    $user = User::query()->where('nick', 'portada_cuenta')->firstOrFail();
    $path = '';

    try {
        $saved = test()->actingAs($user, 'sanctum')
            ->withHeaders(deviceHeaders())
            ->post('/api/v1/me/cover', [
                'cover' => UploadedFile::fake()->image('portada.jpg'),
            ])
            ->assertOk()
            ->assertJsonMissingPath('data.email');

        $path = (string) $saved->json('data.cover');
        expect($path)->toStartWith('/media/covers/')
            ->and($path)->not->toContain('/storage/')
            ->and(is_file(public_path(ltrim($path, '/'))))->toBeTrue();

        test()->actingAs($user, 'sanctum')
            ->withHeaders(deviceHeaders())
            ->post('/api/v1/me/cover', [
                'cover' => UploadedFile::fake()->create('nota.txt', 10, 'text/plain'),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cover.0', 'La foto tiene que ser JPG, PNG, WEBP o GIF.');
    } finally {
        $file = public_path(ltrim($path, '/'));
        if ($path !== '' && is_file($file)) {
            unlink($file);
        }
    }
});

it('requires a session to change the cover photo', function () {
    $this->postJson('/api/v1/me/cover')->assertUnauthorized();
});
