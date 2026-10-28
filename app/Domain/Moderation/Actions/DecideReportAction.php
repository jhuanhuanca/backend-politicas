<?php

namespace App\Domain\Moderation\Actions;

use App\Models\AdminAuditLog;
use App\Models\Report;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideReportAction
{
    public function execute(Staff $staff, Report $report, string $status): Report
    {
        return DB::transaction(function () use ($staff, $report, $status): Report {
            $report = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($report->status !== 'open') {
                throw ValidationException::withMessages([
                    'status' => 'Este reporte ya tiene una decisión.',
                ]);
            }

            $report->update(['status' => $status]);

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => $status === 'resolved' ? 'report.resolved' : 'report.dismissed',
                'subject_type' => 'report',
                'subject_id' => $report->id,
                'created_at' => now(),
            ]);

            return $report->refresh();
        });
    }
}
