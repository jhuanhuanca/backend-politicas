<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\DeviceAlreadyRegisteredException;
use App\Domain\Identity\Exceptions\InvalidCredentialsException;
use App\Domain\Identity\Services\DeviceFingerprintService;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    public function __construct(private readonly DeviceFingerprintService $fingerprints) {}

    /**
     * @return array{user: User, token: string}
     */
    public function execute(LoginRequest $request): array
    {
        $fingerprint = $this->fingerprints->generate($request);
        $nick = $request->string('nick')->toString();
        $password = $request->string('password')->toString();

        $account = User::query()->where('nick', $nick)->first();

        if ($account === null || ! is_string($account->password) || ! Hash::check($password, $account->password)) {
            throw new InvalidCredentialsException;
        }

        if ($account->device_fingerprint === $fingerprint) {
            $account->devices()
                ->where('fingerprint', $fingerprint)
                ->update(['last_seen_at' => now()]);

            return [
                'user' => $account,
                'token' => $account->createToken('device')->plainTextToken,
            ];
        }

        if ($account->device_fingerprint !== null) {
            throw new InvalidCredentialsException;
        }

        $user = DB::transaction(function () use ($request, $fingerprint, $account): User {
            $waiting = User::query()
                ->whereKey($account->id)
                ->whereNull('device_fingerprint')
                ->lockForUpdate()
                ->first();

            if ($waiting === null) {
                throw new InvalidCredentialsException;
            }

            $taken = User::query()
                ->where('device_fingerprint', $fingerprint)
                ->lockForUpdate()
                ->exists();

            if ($taken) {
                throw new DeviceAlreadyRegisteredException;
            }

            $waiting->forceFill(['device_fingerprint' => $fingerprint])->save();
            $waiting->devices()->create([
                'fingerprint' => $fingerprint,
                'user_agent' => $request->userAgent(),
                'last_seen_at' => now(),
            ]);

            return $waiting;
        });

        return [
            'user' => $user,
            'token' => $user->createToken('device')->plainTextToken,
        ];
    }
}
