<?php

namespace App\Domain\Metrics\Actions;

use App\Models\LeaderCandidate;
use Illuminate\Support\Collection;

class ListLeaderCandidatesAction
{
    /**
     * @return Collection<int, LeaderCandidate>
     */
    public function execute(?string $search = null, ?string $department = null, ?string $municipality = null): Collection
    {
        $like = $this->like($search);

        return LeaderCandidate::query()
            ->whereHas('user.consents', function ($query): void {
                $query->where('type', 'metrics')->whereNotNull('granted_at');
            })
            ->when($department, function ($query) use ($department): void {
                $query->whereHas('user', fn ($user) => $user->where('department', $department));
            })
            ->when($municipality, function ($query) use ($municipality): void {
                $query->whereHas('user', fn ($user) => $user->where('municipality', $municipality));
            })
            ->when($like, function ($query) use ($like): void {
                $query->whereHas('user', function ($user) use ($like): void {
                    $user->where(function ($inner) use ($like): void {
                        $inner->where('nick', 'like', $like)->orWhere('name', 'like', $like);
                    });
                });
            })
            ->with('user:id,nick,name,department,municipality')
            ->get()
            ->sort(function (LeaderCandidate $left, LeaderCandidate $right): int {
                $rank = $this->rank($left->status) <=> $this->rank($right->status);

                if ($rank !== 0) {
                    return $rank;
                }

                return $right->participation <=> $left->participation;
            })
            ->values();
    }

    private function like(?string $search): ?string
    {
        $term = trim((string) $search);
        if ($term === '') {
            return null;
        }

        return '%'.str_replace(['%', '_'], '', $term).'%';
    }

    private function rank(string $status): int
    {
        return match ($status) {
            'pending' => 0,
            'accepted' => 1,
            default => 2,
        };
    }
}
