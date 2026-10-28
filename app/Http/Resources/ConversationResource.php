<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;
        $other = $this->low_user_id === $viewerId ? $this->highUser : $this->lowUser;

        return [
            'id' => $this->id,
            'nick' => $other?->nick,
            'last_body' => $this->latestMessage?->body,
            'last_at' => $this->latestMessage?->created_at?->toIso8601String(),
        ];
    }
}
