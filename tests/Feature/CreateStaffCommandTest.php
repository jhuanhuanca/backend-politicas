<?php

use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

it('creates a staff account from the terminal', function () {
    $this->artisan('staff:create')
        ->expectsQuestion('Nombre', 'Mesa de moderación')
        ->expectsQuestion('Correo', 'Mesa@Example.com')
        ->expectsQuestion('Contraseña', 'clave-segura')
        ->expectsQuestion('Confirma la contraseña', 'clave-segura')
        ->expectsOutputToContain('mesa@example.com')
        ->assertSuccessful();

    $staff = Staff::query()->where('email', 'mesa@example.com')->first();

    expect($staff)->not->toBeNull()
        ->and($staff->name)->toBe('Mesa de moderación')
        ->and($staff->role)->toBe('moderator')
        ->and(Hash::check('clave-segura', $staff->password))->toBeTrue()
        ->and($staff->password)->not->toBe('clave-segura');
});

it('rejects a staff account when the password confirmation does not match', function () {
    $this->artisan('staff:create')
        ->expectsQuestion('Nombre', 'Mesa de moderación')
        ->expectsQuestion('Correo', 'mesa@example.com')
        ->expectsQuestion('Contraseña', 'clave-segura')
        ->expectsQuestion('Confirma la contraseña', 'otra-clave')
        ->assertFailed();

    expect(Staff::query()->count())->toBe(0);
});

it('rejects a second staff account with the same email', function () {
    Staff::factory()->create(['email' => 'mesa@example.com']);

    $this->artisan('staff:create')
        ->expectsQuestion('Nombre', 'Otra mesa')
        ->expectsQuestion('Correo', 'mesa@example.com')
        ->expectsQuestion('Contraseña', 'clave-segura')
        ->expectsQuestion('Confirma la contraseña', 'clave-segura')
        ->assertFailed();

    expect(Staff::query()->count())->toBe(1);
});
