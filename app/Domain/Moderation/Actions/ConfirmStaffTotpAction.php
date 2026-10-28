<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Exceptions\StaffTotpException;
use App\Domain\Moderation\Services\StaffLoginChallenge;
use App\Domain\Moderation\Services\Totp;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;

class ConfirmStaffTotpAction
{
    public function __construct(
        private readonly Totp $totp,
        private readonly StaffLoginChallenge $challenges,
    ) {}

    /**
     * @return array{token: string, name: string, role: string}
     */
    public function execute(string $challenge, string $code): array
    {
        $pending = $this->challenges->find($challenge);

        if ($pending === null || $pending['staff']->totp_secret === null) {
            throw new StaffTotpException('expirado');
        }

        $staff = $pending['staff'];
        $step = $this->totp->match($staff->totp_secret, $code, $staff->totp_last_step);

        if ($step === null) {
            $attempts = $this->challenges->recordFailure($challenge);

            throw new StaffTotpException($attempts >= 5 ? 'expirado' : 'codigo');
        }

        return DB::transaction(function () use ($staff, $step, $challenge): array {
            $enrolling = $staff->totp_confirmed_at === null;
            $staff->forceFill([
                'totp_confirmed_at' => $staff->totp_confirmed_at ?? now(),
                'totp_last_step' => $step,
            ])->save();

            $token = $staff->createToken('admin', ['moderation'], now()->addHour());

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => 'staff.login',
                'created_at' => now(),
            ]);

            if ($enrolling) {
                AdminAuditLog::query()->create([
                    'staff_id' => $staff->id,
                    'action' => 'staff.totp_enrolled',
                    'created_at' => now(),
                ]);
            }

            $this->challenges->forget($challenge);

            return [
                'token' => $token->plainTextToken,
                'name' => $staff->name,
                'role' => $staff->role,
            ];
        });
    }
}
