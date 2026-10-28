<?php

use Illuminate\Support\Facades\Route;

it('publishes an OpenAPI document for every v1 route', function () {
    $spec = $this->getJson('/api/v1/openapi.json')
        ->assertOk()
        ->json();

    expect($spec['openapi'])->toBe('3.0.3')
        ->and($spec['info']['title'])->toBe('Política API');

    $documented = [];
    foreach ($spec['paths'] as $path => $operations) {
        foreach ($operations as $method => $operation) {
            if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                continue;
            }

            $documented[] = strtoupper($method).' /api/v1'.$path;
        }
    }

    $actual = [];
    foreach (Route::getRoutes() as $route) {
        $uri = $route->uri();
        if (! str_starts_with($uri, 'api/v1')) {
            continue;
        }

        foreach ($route->methods() as $method) {
            if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                continue;
            }

            $actual[] = $method.' /'.$uri;
        }
    }

    sort($documented);
    sort($actual);

    expect($documented)->toBe($actual);
});

it('keeps personal fields out of the public debate schema', function () {
    $debate = $this->getJson('/api/v1/openapi.json')->json('components.schemas.Debate');
    $encoded = json_encode($debate);

    expect($debate['properties'])->not->toHaveKey('user_id')
        ->and($debate['properties']['author'])->toBeArray()
        ->and($encoded)->not->toContain('device_fingerprint');
});
