<?php

use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('saves a profile photo under media and shows it on the debate', function () {
    test()->withHeaders(deviceHeaders())->postJson('/api/v1/auth/register', account([
        'nick' => 'foto_perfil',
        'consent_terms' => true,
        'consent_metrics' => false,
    ]))->assertCreated();

    $user = User::query()->where('nick', 'foto_perfil')->firstOrFail();
    $paths = [];

    try {
        $first = test()->actingAs($user, 'sanctum')
            ->withHeaders(deviceHeaders())
            ->post('/api/v1/me/avatar', [
                'avatar' => UploadedFile::fake()->image('cara.jpg'),
            ])
            ->assertOk()
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.device_fingerprint');

        $firstPath = (string) $first->json('data.avatar');
        $paths[] = $firstPath;
        expect($firstPath)->toStartWith('/media/avatars/')
            ->and($firstPath)->not->toContain('/storage/')
            ->and(is_file(public_path(ltrim($firstPath, '/'))))->toBeTrue();

        $second = test()->actingAs($user, 'sanctum')
            ->withHeaders(deviceHeaders())
            ->post('/api/v1/me/avatar', [
                'avatar' => UploadedFile::fake()->image('otra.png'),
            ])
            ->assertOk();

        $secondPath = (string) $second->json('data.avatar');
        $paths[] = $secondPath;
        expect($secondPath)->toStartWith('/media/avatars/')
            ->and(is_file(public_path(ltrim($secondPath, '/'))))->toBeTrue()
            ->and(is_file(public_path(ltrim($firstPath, '/'))))->toBeFalse();

        Topic::query()->create([
            'user_id' => $user->id,
            'body' => 'La foto nueva tiene que verse en el debate.',
            'kind' => 'image',
            'likes_count' => 0,
            'shares_count' => 0,
        ]);

        test()->getJson('/api/v1/debates')
            ->assertOk()
            ->assertJsonPath('data.0.author.avatar', $secondPath)
            ->assertJsonMissingPath('data.0.author.email');

        test()->actingAs($user, 'sanctum')
            ->withHeaders(deviceHeaders())
            ->post('/api/v1/me/avatar', [
                'avatar' => UploadedFile::fake()->create('nota.txt', 10, 'text/plain'),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.avatar.0', 'La foto tiene que ser JPG, PNG, WEBP o GIF.');
    } finally {
        foreach ($paths as $path) {
            $file = public_path(ltrim($path, '/'));
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
});

it('requires the account device to change the profile photo', function () {
    $this->postJson('/api/v1/me/avatar')->assertUnauthorized();
});
