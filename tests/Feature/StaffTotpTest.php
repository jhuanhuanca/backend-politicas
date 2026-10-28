<?php

use App\Domain\Moderation\Services\Totp;
use App\Models\Staff;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

it('asks for a second factor and rejects the same code twice', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-15 15:00:15'));
    $staff = Staff::factory()->create([
        'email' => 'mesa@localhost',
        'password' => 'clave-segura',
    ]);

    $pending = $this->withHeaders(adminHeaders())
        ->postJson('/api/admin/auth/login', [
            'email' => 'mesa@localhost',
            'password' => 'clave-segura',
        ])
        ->assertOk()
        ->assertJsonPath('data.pending', 'setup');

    expect($pending->json('data.secret'))->toHaveLength(32);
    expect($pending->getContent())->not->toContain('token');

    $secret = $pending->json('data.secret');
    $code = app(Totp::class)->code($secret);

    $this->withHeaders(adminHeaders())
        ->postJson('/api/admin/auth/totp', [
            'challenge' => $pending->json('data.challenge'),
            'code' => '000000',
        ])
        ->assertUnauthorized()
        ->assertJsonPath('errors.auth.0', 'El código no coincide.');

    $token = $this->withHeaders(adminHeaders())
        ->postJson('/api/admin/auth/totp', [
            'challenge' => $pending->json('data.challenge'),
            'code' => $code,
        ])
        ->assertOk()
        ->json('data.token');

    $this->withHeaders([
        ...adminHeaders(),
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/admin/me')
        ->assertOk()
        ->assertJsonPath('data.name', 'Mesa de moderación');

    expect(PersonalAccessToken::findToken($token)?->expires_at?->equalTo(Carbon::parse('2026-10-15 16:00:15')))->toBeTrue();

    $again = $this->withHeaders(adminHeaders())
        ->postJson('/api/admin/auth/login', [
            'email' => 'mesa@localhost',
            'password' => 'clave-segura',
        ])
        ->assertOk()
        ->assertJsonPath('data.pending', 'code')
        ->assertJsonMissingPath('data.secret');

    $this->withHeaders(adminHeaders())
        ->postJson('/api/admin/auth/totp', [
            'challenge' => $again->json('data.challenge'),
            'code' => $code,
        ])
        ->assertUnauthorized();

    $this->assertDatabaseHas('admin_audit_logs', [
        'staff_id' => $staff->id,
        'action' => 'staff.totp_enrolled',
    ]);

    Carbon::setTestNow();
});
