<?php

namespace App\Domain\Moderation\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListUsersAction
{
    /**
     * @return Collection<int, User>
     */
    public function execute(?string $search = null, ?string $department = null, ?string $municipality = null): Collection
    {
        $like = $this->like($search);

        return User::query()
            ->when($department, fn ($query) => $query->where('department', $department))
            ->when($municipality, fn ($query) => $query->where('municipality', $municipality))
            ->when($like, function ($query) use ($like): void {
                $query->where(function ($inner) use ($like): void {
                    $inner->where('nick', 'like', $like)->orWhere('name', 'like', $like);
                });
            })
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

    private function like(?string $search): ?string
    {
        $term = trim((string) $search);
        if ($term === '') {
            return null;
        }

        return '%'.str_replace(['%', '_'], '', $term).'%';
    }
}
