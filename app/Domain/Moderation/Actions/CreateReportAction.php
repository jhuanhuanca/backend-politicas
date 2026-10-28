<?php

namespace App\Domain\Moderation\Actions;

use App\Models\Argument;
use App\Models\NewsItem;
use App\Models\Report;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReportAction
{
    public function execute(User $user, string $targetType, int $targetId, string $reason, ?string $body): Report
    {
        $target = $this->target($targetType, $targetId);

        try {
            return DB::transaction(function () use ($user, $target, $reason, $body): Report {
                $existing = Report::query()
                    ->where('user_id', $user->id)
                    ->where('reportable_type', $target::class)
                    ->where('reportable_id', $target->getKey())
                    ->lockForUpdate()
                    ->exists();

                if ($existing) {
                    $this->duplicate();
                }

                return Report::query()->create([
                    'user_id' => $user->id,
                    'reportable_type' => $target::class,
                    'reportable_id' => $target->getKey(),
                    'reason' => $reason,
                    'body' => $body,
                    'status' => 'open',
                ]);
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }

            $this->duplicate();
        }
    }

    private function target(string $targetType, int $targetId): Model
    {
        $model = match ($targetType) {
            'debate' => Topic::query()->find($targetId),
            'argument' => Argument::query()->find($targetId),
            'news' => NewsItem::query()->find($targetId),
            default => null,
        };

        if ($model === null) {
            throw ValidationException::withMessages([
                'target_id' => 'No encontramos eso para reportar.',
            ]);
        }

        return $model;
    }

    private function duplicate(): never
    {
        throw ValidationException::withMessages([
            'target_id' => 'Ya enviaste un reporte de esto.',
        ]);
    }
}
