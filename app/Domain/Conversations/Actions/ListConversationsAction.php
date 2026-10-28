<?php

namespace App\Domain\Conversations\Actions;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListConversationsAction
{
    /**
     * @return Collection<int, Conversation>
     */
    public function execute(User $user): Collection
    {
        return Conversation::query()
            ->where(function ($query) use ($user): void {
                $query->where('low_user_id', $user->id)
                    ->orWhere('high_user_id', $user->id);
            })
            ->with([
                'lowUser:id,nick',
                'highUser:id,nick',
                'latestMessage',
            ])
            ->latest('updated_at')
            ->limit(30)
            ->get();
    }
}
