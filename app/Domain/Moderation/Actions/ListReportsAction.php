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
                'user:id,nick,name,department,municipality',
                'reportable' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Topic::class => ['user:id,nick,name,department,municipality'],
                        Argument::class => ['user:id,nick,name,department,municipality'],
                    ]);
                },
            ])
            ->latest()
            ->limit(50)
            ->get();
    }
}
