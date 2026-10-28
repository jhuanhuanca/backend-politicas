<?php

namespace App\Domain\Conversations\Actions;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenConversationAction
{
    public function execute(User $user, string $nick): ?Conversation
    {
        if ($user->nick === $nick) {
            throw ValidationException::withMessages([
                'nick' => 'No puedes conversar contigo.',
            ]);
        }

        $other = User::query()->where('nick', $nick)->first();
        if ($other === null) {
            return null;
        }

        $low = min($user->id, $other->id);
        $high = max($user->id, $other->id);

        return DB::transaction(function () use ($low, $high): Conversation {
            return Conversation::query()->firstOrCreate([
                'low_user_id' => $low,
                'high_user_id' => $high,
            ])->load([
                'lowUser:id,nick',
                'highUser:id,nick',
                'latestMessage',
            ]);
        });
    }
}
