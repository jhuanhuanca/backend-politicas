<?php

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

it('returns a request id on public responses', function () {
    $response = $this->getJson('/api/v1/ping');

    $response->assertOk();
    expect($response->headers->get('X-Request-Id'))->toMatch('/^[0-9a-f-]{36}$/');
});

it('keeps a safe incoming request id', function () {
    $this->withHeader('X-Request-Id', 'seguimiento_abc-12')
        ->getJson('/api/v1/ping')
        ->assertHeader('X-Request-Id', 'seguimiento_abc-12');
});

it('replaces a request id that is not safe to store', function () {
    $header = $this->withHeader('X-Request-Id', "corte\nlinea")
        ->getJson('/api/v1/ping')
        ->headers->get('X-Request-Id');

    expect($header)->toMatch('/^[0-9a-f-]{36}$/');
});

it('hides unexpected failures and logs only the exception class', function () {
    $entries = [];
    Log::listen(function (MessageLogged $event) use (&$entries): void {
        $entries[] = $event;
    });

    Route::middleware('api')->get('/api/v1/observability-probe', function (): void {
        throw new RuntimeException('nick secreto en el mensaje');
    });

    $response = $this->getJson('/api/v1/observability-probe');

    $response->assertStatus(500)
        ->assertJsonPath('errors.server.0', 'No pudimos completar eso.');

    $requestId = $response->headers->get('X-Request-Id');
    expect($requestId)->toMatch('/^[0-9a-f-]{36}$/')
        ->and($response->json('meta.request_id'))->toBe($requestId)
        ->and($response->getContent())->not->toContain('nick secreto');

    $failure = collect($entries)->first(fn (MessageLogged $event): bool => $event->message === 'api.failure');

    expect($failure)->not->toBeNull()
        ->and($failure->context['request_id'] ?? null)->toBe($requestId)
        ->and($failure->context['exception'] ?? null)->toBe(RuntimeException::class)
        ->and($failure->context['path'] ?? null)->toBe('api/v1/observability-probe')
        ->and(json_encode($failure->context))->not->toContain('nick secreto');
});

it('keeps validation errors out of the failure log', function () {
    $entries = [];
    Log::listen(function (MessageLogged $event) use (&$entries): void {
        $entries[] = $event;
    });

    $this->getJson('/api/v1/search?q=a')->assertStatus(422);

    expect(collect($entries)->contains(fn (MessageLogged $event): bool => $event->message === 'api.failure'))->toBeFalse();
});
