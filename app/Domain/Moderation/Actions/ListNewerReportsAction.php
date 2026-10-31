<?php

namespace App\Domain\Moderation\Actions;

use App\Models\Argument;
use App\Models\Report;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ListNewerReportsAction
{
    /**
     * @return Collection<int, Report>
     */
    public function execute(int $after): Collection
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
            ->where('status', 'open')
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(20)
            ->get();
    }

    public function latestOpenId(): int
    {
        return (int) (Report::query()->where('status', 'open')->max('id') ?? 0);
    }
}
