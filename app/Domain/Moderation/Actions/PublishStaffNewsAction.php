<?php

namespace App\Domain\Moderation\Actions;

use App\Models\AdminAuditLog;
use App\Models\NewsItem;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublishStaffNewsAction
{
    public function execute(Staff $staff, string $title, string $summary, string $source, ?int $debateId): NewsItem
    {
        return DB::transaction(function () use ($staff, $title, $summary, $source, $debateId): NewsItem {
            $item = NewsItem::query()->create([
                'title' => $title,
                'summary' => $summary,
                'source' => $source,
                'topic_id' => $debateId,
                'guid' => 'staff-'.Str::uuid(),
                'published_at' => now(),
            ]);

            AdminAuditLog::query()->create([
                'staff_id' => $staff->id,
                'action' => 'news.published',
                'subject_type' => 'news',
                'subject_id' => $item->id,
                'created_at' => now(),
            ]);

            return $item;
        });
    }
}
