<?php

use App\Models\Staff;
use Illuminate\Http\UploadedFile;

it('places an announcement and a background on the public heroes', function () {
    Staff::factory()->create([
        'email' => 'portada@localhost',
        'password' => 'clave-segura',
    ]);
    $token = staffSessionToken('portada@localhost', 'clave-segura');
    $headers = [
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ];

    $saved = $this->withHeaders($headers)
        ->post('/api/admin/heroes', [
            'slot' => 'home',
            'announcement' => 'Audiencia del presupuesto, martes 18:00.',
            'media' => UploadedFile::fake()->image('fondo.jpg'),
        ])
        ->assertOk()
        ->assertJsonPath('data.slot', 'home')
        ->assertJsonPath('data.kind', 'image')
        ->assertJsonPath('data.announcement', 'Audiencia del presupuesto, martes 18:00.');

    $path = (string) $saved->json('data.media');
    expect($path)->toStartWith('/media/banners/')
        ->and(is_file(public_path(ltrim($path, '/'))))->toBeTrue();

    $this->getJson('/api/v1/heroes')
        ->assertOk()
        ->assertJsonPath('data.home.announcement', 'Audiencia del presupuesto, martes 18:00.')
        ->assertJsonPath('data.home.media', $path)
        ->assertJsonPath('data.polls.media', null);

    $this->withHeaders($headers)
        ->postJson('/api/admin/heroes', [
            'slot' => 'polls',
            'link' => 'https://example.com/votacion.mp4',
        ])
        ->assertOk()
        ->assertJsonPath('data.kind', 'video')
        ->assertJsonPath('data.media', 'https://example.com/votacion.mp4');

    $this->withHeaders($headers)
        ->post('/api/admin/heroes', [
            'slot' => 'home',
            'clear' => '1',
            'media' => UploadedFile::fake()->image('otra.jpg'),
            'link' => 'https://example.com/otra.jpg',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.media.0', 'Elige un archivo o un enlace, no los dos.');

    $this->withHeaders($headers)
        ->post('/api/admin/heroes', [
            'slot' => 'home',
            'clear' => '1',
        ])
        ->assertOk()
        ->assertJsonPath('data.media', null)
        ->assertJsonPath('data.kind', null);

    expect(is_file(public_path(ltrim($path, '/'))))->toBeFalse();

    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'hero.updated',
        'subject_type' => 'hero',
    ]);
});

it('accepts an image link without a file extension and a youtube link', function () {
    Staff::factory()->create([
        'email' => 'portada-enlace@localhost',
        'password' => 'clave-segura',
    ]);
    $token = staffSessionToken('portada-enlace@localhost', 'clave-segura');
    $headers = [
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ];

    $this->withHeaders($headers)
        ->postJson('/api/admin/heroes', [
            'slot' => 'home',
            'link' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
        ])
        ->assertOk()
        ->assertJsonPath('data.kind', 'image')
        ->assertJsonPath('data.media', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee');

    $this->withHeaders($headers)
        ->postJson('/api/admin/heroes', [
            'slot' => 'polls',
            'link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])
        ->assertOk()
        ->assertJsonPath('data.kind', 'video')
        ->assertJsonPath('data.media', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    $this->withHeaders($headers)
        ->postJson('/api/admin/heroes', [
            'slot' => 'home',
            'link' => 'http://example.com/fondo.jpg',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.link.0', 'El enlace tiene que empezar por https://.');
});
