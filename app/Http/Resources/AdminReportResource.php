<?php

namespace App\Http\Resources;

use App\Models\Argument;
use App\Models\NewsItem;
use App\Models\Report;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Report
 */
class AdminReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'reason' => $this->reason,
            'body' => $this->body,
            'target_type' => $this->targetType(),
            'target_id' => $this->reportable_id,
            'excerpt' => $this->excerpt(),
            'reporter_nick' => $this->user?->nick,
            'author_nick' => $this->authorNick(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function authorNick(): ?string
    {
        $target = $this->reportable;

        if ($target instanceof Topic || $target instanceof Argument) {
            return $target->user?->nick;
        }

        return null;
    }

    private function targetType(): string
    {
        return match ($this->reportable_type) {
            Topic::class => 'debate',
            Argument::class => 'argument',
            NewsItem::class => 'news',
            default => 'unknown',
        };
    }

    private function excerpt(): string
    {
        $target = $this->reportable;

        if ($target instanceof Topic || $target instanceof Argument) {
            return Str::limit((string) $target->body, 160);
        }

        if ($target instanceof NewsItem) {
            return Str::limit((string) $target->title, 160);
        }

        return 'El contenido ya no está.';
    }
}
