<?php

namespace App\Domain\Moderation\Actions;

use App\Models\AdminAuditLog;
use App\Models\Staff;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublishStaffDebateAction
{
    public function execute(Staff $staff, string $body, string $context, ?string $image, string $kind): Topic
    {
        return DB::transaction(function () use ($staff, $body, $context, $image, $kind): Topic {
            $author = $this->desk();
            $topic = Topic::query()->create([
                'user_id' => $author->id,
                'body' => $body,
                'context' => $context,
                'image' => $image,
                'kind' => $image === null ? 'text' : $kind,
                'likes_count' => 0,
                'shares_count' => 0,
            ]);

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => 'debate.published',
                'subject_type' => 'debate',
                'subject_id' => $topic->id,
                'created_at' => now(),
            ]);

            return $topic->load('user:id,nick');
        });
    }

    private function desk(): User
    {
        $fingerprint = hash('sha256', 'staff-desk');
        $author = User::query()->where('nick', 'mesa')->lockForUpdate()->first();

        if ($author === null) {
            return User::query()->create([
                'name' => 'Mesa',
                'nick' => 'mesa',
                'device_fingerprint' => $fingerprint,
                'password' => Str::password(40),
            ]);
        }

        if ($author->device_fingerprint !== $fingerprint) {
            throw ValidationException::withMessages([
                'body' => 'El nick mesa ya pertenece a otra cuenta.',
            ]);
        }

        return $author;
    }
}
