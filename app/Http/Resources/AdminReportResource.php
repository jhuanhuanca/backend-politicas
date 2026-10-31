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
            'reporter_name' => $this->user?->name,
            'reporter_department' => $this->user?->department,
            'reporter_municipality' => $this->user?->municipality,
            'author_nick' => $this->authorNick(),
            'author_name' => $this->authorField('name'),
            'author_department' => $this->authorField('department'),
            'author_municipality' => $this->authorField('municipality'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function authorNick(): ?string
    {
        return $this->authorField('nick');
    }

    private function authorField(string $field): ?string
    {
        $target = $this->reportable;

        if ($target instanceof Topic || $target instanceof Argument) {
            $value = $target->user?->{$field};

            return is_string($value) && $value !== '' ? $value : null;
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
