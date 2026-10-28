<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\DeviceAlreadyRegisteredException;
use App\Domain\Identity\Services\DeviceFingerprintService;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\WelcomeAccountMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RegisterUserAction
{
    public function __construct(private readonly DeviceFingerprintService $fingerprints) {}

    /**
     * @return array{user: User, token: string}
     */
    public function execute(RegisterRequest $request): array
    {
        $fingerprint = $this->fingerprints->generate($request);

        $alreadyRegistered = User::query()
            ->where('device_fingerprint', $fingerprint)
            ->exists();

        if ($alreadyRegistered) {
            throw new DeviceAlreadyRegisteredException;
        }

        $user = DB::transaction(function () use ($request, $fingerprint): User {
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'nick' => $request->string('nick')->toString(),
                'department' => $request->string('department')->toString(),
                'birth_year' => $request->integer('birth_year'),
                'password' => $request->string('password')->toString(),
                'device_fingerprint' => $fingerprint,
            ]);

            $user->consents()->createMany([
                [
                    'type' => 'terms',
                    'granted_at' => now(),
                ],
                [
                    'type' => 'metrics',
                    'granted_at' => $request->boolean('consent_metrics') ? now() : null,
                ],
            ]);

            $user->devices()->create([
                'fingerprint' => $fingerprint,
                'user_agent' => $request->userAgent(),
                'last_seen_at' => now(),
            ]);

            return $user;
        });

        $this->sendWelcome($user);

        return [
            'user' => $user,
            'token' => $user->createToken('device')->plainTextToken,
        ];
    }

    private function sendWelcome(User $user): void
    {
        if (! is_string($user->email) || $user->email === '' || ! is_string($user->name)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new WelcomeAccountMail($user->name, $user->nick));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
