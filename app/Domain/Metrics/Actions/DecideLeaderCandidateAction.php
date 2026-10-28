<?php

namespace App\Domain\Metrics\Actions;

use App\Models\AdminAuditLog;
use App\Models\LeaderCandidate;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideLeaderCandidateAction
{
    public function execute(Staff $staff, LeaderCandidate $candidate, string $status): LeaderCandidate
    {
        return DB::transaction(function () use ($staff, $candidate, $status): LeaderCandidate {
            $candidate = LeaderCandidate::query()->whereKey($candidate->id)->lockForUpdate()->firstOrFail();

            $consented = $candidate->user?->consents()
                ->where('type', 'metrics')
                ->whereNotNull('granted_at')
                ->exists() ?? false;

            if (! $consented) {
                throw ValidationException::withMessages([
                    'status' => 'Esta persona ya no tiene el consentimiento de métricas.',
                ]);
            }

            if ($candidate->status === $status) {
                throw ValidationException::withMessages([
                    'status' => 'Este candidato ya está en ese estado.',
                ]);
            }

            $candidate->update(['status' => $status]);

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => match ($status) {
                    'accepted' => 'leader.accepted',
                    'set_aside' => 'leader.set_aside',
                    default => 'leader.pending',
                },
                'subject_type' => 'leader',
                'subject_id' => $candidate->id,
                'created_at' => now(),
            ]);

            return $candidate->refresh()->load('user:id,nick');
        });
    }
}
