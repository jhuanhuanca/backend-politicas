<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Exceptions\StaffCredentialsException;
use App\Domain\Moderation\Services\StaffLoginChallenge;
use App\Domain\Moderation\Services\Totp;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

class LoginStaffAction
{
    public function __construct(
        private readonly Totp $totp,
        private readonly StaffLoginChallenge $challenges,
    ) {}

    /**
     * @return array{pending: string, challenge: string, secret?: string, otpauth?: string}
     */
    public function execute(string $email, string $password): array
    {
        $staff = Staff::query()->where('email', $email)->first();

        if ($staff === null || $staff->role !== 'moderator' || ! Hash::check($password, $staff->password)) {
            throw new StaffCredentialsException;
        }

        if ($staff->totp_secret === null) {
            $staff->forceFill([
                'totp_secret' => $this->totp->generateSecret(),
            ])->save();
        }

        $pending = $staff->totp_confirmed_at === null ? 'setup' : 'code';
        $result = [
            'pending' => $pending,
            'challenge' => $this->challenges->issue($staff, $pending),
        ];

        if ($pending === 'setup') {
            $result['secret'] = $staff->totp_secret;
            $result['otpauth'] = $this->totp->provisioningUri($staff->totp_secret, $staff->email);
        }

        return $result;
    }
}
