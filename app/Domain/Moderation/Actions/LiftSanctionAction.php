<?php

namespace App\Domain\Moderation\Actions;

use App\Models\AdminAuditLog;
use App\Models\Sanction;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LiftSanctionAction
{
    public function execute(Staff $staff, Sanction $sanction): Sanction
    {
        return DB::transaction(function () use ($staff, $sanction): Sanction {
            $sanction = Sanction::query()->whereKey($sanction->id)->lockForUpdate()->firstOrFail();

            if (! $sanction->isActive()) {
                throw ValidationException::withMessages([
                    'sanction' => 'Esta sanción ya no está vigente.',
                ]);
            }

            $sanction->update(['lifted_at' => now()]);

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => 'sanction.lifted',
                'subject_type' => 'sanction',
                'subject_id' => $sanction->id,
                'created_at' => now(),
            ]);

            return $sanction->refresh()->load('user:id,nick');
        });
    }
}
