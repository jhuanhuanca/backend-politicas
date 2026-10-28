<?php

namespace App\Domain\Moderation\Actions;

use App\Models\Sanction;
use Illuminate\Database\Eloquent\Collection;

class ListSanctionsAction
{
    /**
     * @return Collection<int, Sanction>
     */
    public function execute(): Collection
    {
        return Sanction::query()
            ->with('user:id,nick')
            ->whereNull('lifted_at')
            ->where('ends_at', '>', now())
            ->orderBy('ends_at')
            ->limit(50)
            ->get();
    }
}
