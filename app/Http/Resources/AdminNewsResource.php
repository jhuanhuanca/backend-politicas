<?php

namespace App\Http\Resources;

use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NewsItem
 */
class AdminNewsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'source' => $this->source,
            'debate_id' => $this->topic_id,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
