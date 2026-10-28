<?php

it('copies the sqlite file into a private backup', function () {
    $directory = storage_path('framework/testing');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $source = $directory.'/backup-source.sqlite';
    file_put_contents($source, 'sqlite-backup-marker');

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.driver' => 'sqlite',
        'database.connections.sqlite.database' => $source,
    ]);

    $this->artisan('backup:database')
        ->assertSuccessful();

    $match = collect(glob(storage_path('app/private/backups/database-*.sqlite')) ?: [])
        ->first(fn (string $file): bool => file_get_contents($file) === 'sqlite-backup-marker');

    expect($match)->toBeString()
        ->and($match)->not->toContain('public');

    unlink($match);
    unlink($source);
});

it('allows the local frontend origins', function () {
    expect(config('cors.allowed_origins'))->toContain('http://127.0.0.1:5173')
        ->and(config('cors.allowed_origins'))->toContain('http://localhost:5173');
});
