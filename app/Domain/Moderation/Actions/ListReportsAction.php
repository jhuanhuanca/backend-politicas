<?php

namespace App\Domain\Moderation\Actions;

use App\Models\Argument;
use App\Models\Report;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ListReportsAction
{
    /**
     * @return Collection<int, Report>
     */
    public function execute(): Collection
    {
        return Report::query()
            ->with([
                'user:id,nick',
                'reportable' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Topic::class => ['user:id,nick'],
                        Argument::class => ['user:id,nick'],
                    ]);
                },
            ])
            ->latest()
            ->limit(50)
            ->get();
    }
}
