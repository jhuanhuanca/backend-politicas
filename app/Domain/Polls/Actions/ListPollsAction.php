<?php

namespace App\Domain\Polls\Actions;

use App\Models\Poll;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListPollsAction
{
    /**
     * @return Collection<int, Poll>
     */
    public function execute(?User $viewer): Collection
    {
        return Poll::query()
            ->with([
                'options',
                'votes' => function ($query) use ($viewer): void {
                    if ($viewer === null) {
                        $query->whereKey([]);

                        return;
                    }

                    $query->where('user_id', $viewer->id);
                },
            ])
            ->orderBy('position')
            ->get();
    }
}
