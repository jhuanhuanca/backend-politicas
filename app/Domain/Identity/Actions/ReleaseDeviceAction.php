<?php

namespace App\Domain\Identity\Actions;

use App\Models\AdminAuditLog;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReleaseDeviceAction
{
    public function execute(Staff $staff, User $user): User
    {
        return DB::transaction(function () use ($staff, $user): User {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($user->device_fingerprint === null) {
                throw ValidationException::withMessages([
                    'device' => 'Esta cuenta ya está a la espera de un dispositivo.',
                ]);
            }

            $user->tokens()->delete();
            $user->devices()->delete();
            $user->forceFill(['device_fingerprint' => null])->save();

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => 'device.released',
                'subject_type' => 'user',
                'subject_id' => $user->id,
                'created_at' => now(),
            ]);

            return $user->refresh()->load([
                'devices' => fn ($query) => $query
                    ->select(['id', 'user_id', 'last_seen_at'])
                    ->latest('last_seen_at'),
                'consents' => fn ($query) => $query
                    ->select(['id', 'user_id', 'type', 'granted_at'])
                    ->where('type', 'metrics'),
            ]);
        });
    }
}
