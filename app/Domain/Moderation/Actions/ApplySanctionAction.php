<?php

namespace App\Domain\Moderation\Actions;

use App\Models\AdminAuditLog;
use App\Models\Argument;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplySanctionAction
{
    public function execute(Staff $staff, Report $report, int $days): Sanction
    {
        return DB::transaction(function () use ($staff, $report, $days): Sanction {
            $report = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $author = $this->author($report);

            if ($author === null) {
                throw ValidationException::withMessages([
                    'report' => 'Esta noticia no tiene una cuenta para silenciar.',
                ]);
            }

            $active = Sanction::query()
                ->where('user_id', $author->id)
                ->whereNull('lifted_at')
                ->where('ends_at', '>', now())
                ->lockForUpdate()
                ->exists();

            if ($active) {
                throw ValidationException::withMessages([
                    'report' => 'Esta cuenta ya está silenciada.',
                ]);
            }

            $sanction = Sanction::query()->create([
                'user_id' => $author->id,
                'reason' => $report->reason,
                'ends_at' => now()->addDays($days),
            ]);

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => 'sanction.applied',
                'subject_type' => 'sanction',
                'subject_id' => $sanction->id,
                'created_at' => now(),
            ]);

            return $sanction->load('user:id,nick');
        });
    }

    private function author(Report $report): ?User
    {
        $target = $report->reportable;

        if ($target instanceof Topic || $target instanceof Argument) {
            return $target->user;
        }

        return null;
    }
}
