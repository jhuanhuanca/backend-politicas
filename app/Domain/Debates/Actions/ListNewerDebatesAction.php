<?php

namespace App\Domain\Debates\Actions;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;

class ListNewerDebatesAction
{
    private const LIMIT = 20;

    /**
     * @return array{topics: Collection<int, Topic>, latest: int, more: bool}
     */
    public function execute(int $after): array
    {
        $rows = Topic::query()
            ->with('user:id,nick')
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(self::LIMIT + 1)
            ->get();

        $more = $rows->count() > self::LIMIT;
        $page = $rows->take(self::LIMIT)->values();

        return [
            'topics' => $page,
            'latest' => (int) ($page->last()->id ?? $after),
            'more' => $more,
        ];
    }
}
