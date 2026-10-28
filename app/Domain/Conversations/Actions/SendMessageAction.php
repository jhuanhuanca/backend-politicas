<?php

namespace App\Domain\Conversations\Actions;

use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SendMessageAction
{
    public function execute(User $user, Conversation $conversation, string $body): DirectMessage
    {
        return DB::transaction(function () use ($user, $conversation, $body): DirectMessage {
            $message = DirectMessage::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'body' => $body,
            ]);

            $conversation->touch();

            return $message;
        });
    }
}
