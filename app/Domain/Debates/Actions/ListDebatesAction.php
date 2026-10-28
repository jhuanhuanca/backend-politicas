<?php

namespace App\Domain\Debates\Actions;

use App\Domain\Debates\DebateRelations;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListDebatesAction
{
    public function __construct(private DebateRelations $relations) {}

    /**
     * @return Collection<int, Topic>
     */
    public function execute(?User $viewer): Collection
    {
        return Topic::query()
            ->with($this->relations->tree($viewer))
            ->orderByRaw('case when featured_at is null then 1 else 0 end')
            ->orderByDesc('featured_at')
            ->latest()
            ->limit(30)
            ->get();
    }
}
