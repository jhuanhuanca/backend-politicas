<?php

namespace App\Domain\Observability\Actions;

use App\Models\LeaderCandidate;
use App\Models\Report;
use App\Models\Sanction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ShowStatusAction
{
    /**
     * @return array{
     *     database: string,
     *     queue: array{driver: string, pending: int, failed: int},
     *     attention: array{open_reports: int, sanctions: int, leaders: int},
     *     failure: array{name: string, failed_at: string|null}|null
     * }
     */
    public function execute(): array
    {
        if (! $this->databaseResponds()) {
            return [
                'database' => 'down',
                'queue' => [
                    'driver' => $this->driver(),
                    'pending' => 0,
                    'failed' => 0,
                ],
                'attention' => [
                    'open_reports' => 0,
                    'sanctions' => 0,
                    'leaders' => 0,
                ],
                'failure' => null,
            ];
        }

        return [
            'database' => 'ok',
            'queue' => [
                'driver' => $this->driver(),
                'pending' => (int) DB::table('jobs')->count(),
                'failed' => (int) DB::table('failed_jobs')->count(),
            ],
            'attention' => [
                'open_reports' => Report::query()->where('status', 'open')->count(),
                'sanctions' => Sanction::query()
                    ->whereNull('lifted_at')
                    ->where('ends_at', '>', now())
                    ->count(),
                'leaders' => LeaderCandidate::query()
                    ->where('status', 'pending')
                    ->whereHas('user.consents', function ($query): void {
                        $query->where('type', 'metrics')->whereNotNull('granted_at');
                    })
                    ->count(),
            ],
            'failure' => $this->latestFailure(),
        ];
    }

    private function databaseResponds(): bool
    {
        try {
            DB::select('select 1 as ok');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function driver(): string
    {
        return match (config('queue.default')) {
            'sync' => 'inmediata',
            'database' => 'base',
            'redis' => 'redis',
            default => 'otra',
        };
    }

    /**
     * @return array{name: string, failed_at: string|null}|null
     */
    private function latestFailure(): ?array
    {
        $row = DB::table('failed_jobs')->orderByDesc('id')->first(['payload', 'failed_at']);

        if ($row === null) {
            return null;
        }

        $payload = json_decode((string) $row->payload, true);
        $name = is_array($payload) ? ($payload['displayName'] ?? null) : null;
        $short = 'Trabajo';

        if (is_string($name) && preg_match('/^[A-Za-z0-9_\\\\]+$/', $name) === 1) {
            $parts = explode('\\', $name);
            $short = $parts[array_key_last($parts)] ?: 'Trabajo';
        }

        $failedAt = $row->failed_at !== null
            ? Carbon::parse($row->failed_at)->toIso8601String()
            : null;

        return [
            'name' => $short,
            'failed_at' => $failedAt,
        ];
    }
}
