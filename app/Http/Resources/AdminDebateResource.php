<?php

namespace App\Http\Resources;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Topic
 */
class AdminDebateResource extends JsonResource
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
            'category' => $this->category,
            'closed' => $this->closed_at !== null,
            'featured' => $this->featured_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
