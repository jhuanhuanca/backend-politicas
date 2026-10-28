<?php

namespace App\Http\Resources;

use App\Models\DirectMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DirectMessage
 */
class DirectMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'mine' => $this->sender_id === $request->user()?->id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
