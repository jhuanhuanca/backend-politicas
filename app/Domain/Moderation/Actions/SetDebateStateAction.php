<?php

namespace App\Domain\Moderation\Actions;

use App\Models\AdminAuditLog;
use App\Models\Staff;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetDebateStateAction
{
    public function execute(Staff $staff, Topic $topic, string $action): Topic
    {
        return DB::transaction(function () use ($staff, $topic, $action): Topic {
            $topic = Topic::query()->whereKey($topic->id)->lockForUpdate()->firstOrFail();

            match ($action) {
                'close' => $this->close($topic),
                'reopen' => $this->reopen($topic),
                'feature' => $this->feature($topic),
                'unfeature' => $this->unfeature($topic),
                default => throw ValidationException::withMessages([
                    'action' => 'Esa acción no existe.',
                ]),
            };

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => match ($action) {
                    'close' => 'debate.closed',
                    'reopen' => 'debate.reopened',
                    'feature' => 'debate.featured',
                    'unfeature' => 'debate.unfeatured',
                    default => 'debate.updated',
                },
                'subject_type' => 'debate',
                'subject_id' => $topic->id,
                'created_at' => now(),
            ]);

            return $topic->refresh()->load('user:id,nick');
        });
    }

    private function close(Topic $topic): void
    {
        if ($topic->closed_at !== null) {
            throw ValidationException::withMessages([
                'action' => 'Este debate ya está cerrado.',
            ]);
        }

        $topic->forceFill(['closed_at' => now()])->save();
    }

    private function reopen(Topic $topic): void
    {
        if ($topic->closed_at === null) {
            throw ValidationException::withMessages([
                'action' => 'Este debate ya está abierto.',
            ]);
        }

        $topic->forceFill(['closed_at' => null])->save();
    }

    private function feature(Topic $topic): void
    {
        if ($topic->featured_at !== null) {
            throw ValidationException::withMessages([
                'action' => 'Este debate ya está destacado.',
            ]);
        }

        Topic::query()
            ->whereKeyNot($topic->id)
            ->whereNotNull('featured_at')
            ->update(['featured_at' => null]);

        $topic->forceFill(['featured_at' => now()])->save();
    }

    private function unfeature(Topic $topic): void
    {
        if ($topic->featured_at === null) {
            throw ValidationException::withMessages([
                'action' => 'Este debate no está destacado.',
            ]);
        }

        $topic->forceFill(['featured_at' => null])->save();
    }
}
