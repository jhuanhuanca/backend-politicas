<?php

namespace App\Http\Resources;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Topic
 */
class AdminKpiDebateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'excerpt' => Str::limit((string) $this->body, 160),
            'author_nick' => $this->user?->nick,
            'arguments' => (int) $this->arguments_total,
            'replies' => (int) $this->replies_total,
            'reactions' => (int) $this->reactions_total,
            'favor' => (int) $this->favor_total,
            'contra' => (int) $this->contra_total,
            'shares' => (int) $this->shares_count,
        ];
    }
}
