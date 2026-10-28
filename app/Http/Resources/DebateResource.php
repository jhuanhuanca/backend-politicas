<?php

namespace App\Http\Resources;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Topic
 */
class DebateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'image' => $this->image,
            'kind' => $this->kind,
            'context' => $this->context,
            'likes' => $this->likes_count,
            'shares' => $this->shares_count,
            'liked' => $this->relationLoaded('reactions') && $this->reactions->isNotEmpty(),
            'closed' => $this->closed_at !== null,
            'featured' => $this->featured_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => [
                'nick' => $this->user?->nick,
            ],
            'arguments' => $this->relationLoaded('arguments')
                ? ArgumentResource::collection($this->arguments)->resolve()
                : [],
        ];
    }
}
