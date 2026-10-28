<?php

namespace App\Domain\Metrics\Actions;

use App\Models\LeaderCandidate;
use Illuminate\Support\Collection;

class ListLeaderCandidatesAction
{
    /**
     * @return Collection<int, LeaderCandidate>
     */
    public function execute(): Collection
    {
        return LeaderCandidate::query()
            ->whereHas('user.consents', function ($query): void {
                $query->where('type', 'metrics')->whereNotNull('granted_at');
            })
            ->with('user:id,nick')
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

    private function rank(string $status): int
    {
        return match ($status) {
            'pending' => 0,
            'accepted' => 1,
            default => 2,
        };
    }
}
