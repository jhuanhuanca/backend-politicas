<?php

namespace App\Http\Resources;

use App\Models\Argument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Argument
 */
class ArgumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'topic_id' => $this->topic_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'likes' => $this->likes_count,
            'liked' => $this->relationLoaded('reactions') && $this->reactions->isNotEmpty(),
            'favor' => $this->favor_count,
            'contra' => $this->contra_count,
            'stance' => $this->relationLoaded('votes') ? $this->votes->first()?->stance : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => [
                'nick' => $this->user?->nick,
            ],
            'replies' => $this->relationLoaded('replies')
                ? ArgumentResource::collection($this->replies)->resolve()
                : [],
        ];
    }
}
