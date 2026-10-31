<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SuggestPeopleAction
{
    /**
     * @return Collection<int, User>
     */
    public function execute(User $viewer, ?string $search = null): Collection
    {
        $like = $this->like($search);

        return User::query()
            ->select(['id', 'nick', 'avatar'])
            ->where('id', '!=', $viewer->id)
            ->where('nick', '!=', 'mesa')
            ->when($like, fn ($query) => $query->where('nick', 'like', $like))
            ->orderBy('nick')
            ->limit(24)
            ->get();
    }

    private function like(?string $search): ?string
    {
        $term = trim((string) $search);
        if ($term === '') {
            return null;
        }

        return '%'.str_replace(['%', '_'], '', $term).'%';
    }
}
