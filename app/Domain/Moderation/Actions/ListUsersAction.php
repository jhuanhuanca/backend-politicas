<?php

namespace App\Domain\Moderation\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListUsersAction
{
    /**
     * @return Collection<int, User>
     */
    public function execute(): Collection
    {
        return User::query()
            ->with([
                'devices' => fn ($query) => $query
                    ->select(['id', 'user_id', 'last_seen_at'])
                    ->latest('last_seen_at'),
                'consents' => fn ($query) => $query
                    ->select(['id', 'user_id', 'type', 'granted_at'])
                    ->where('type', 'metrics'),
            ])
            ->orderBy('nick')
            ->limit(100)
            ->get();
    }
}
