<?php

namespace App\Domain\Moderation\Actions;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;

class ListStaffDebatesAction
{
    /**
     * @return Collection<int, Topic>
     */
    public function execute(): Collection
    {
        return Topic::query()
            ->with('user:id,nick')
            ->orderByDesc('featured_at')
            ->latest()
            ->limit(50)
            ->get();
    }
}
