<?php

namespace App\Domain\Debates\Actions;

use App\Models\Argument;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListNewerArgumentsAction
{
    private const LIMIT = 30;

    /**
     * @return array{arguments: Collection<int, Argument>, latest: int, more: bool}
     */
    public function execute(Topic $topic, int $after, ?User $viewer): array
    {
        $rows = Argument::query()
            ->where('topic_id', $topic->id)
            ->where('id', '>', $after)
            ->with([
                'user:id,nick',
                'reactions' => fn (Relation $query) => $this->onlyViewer($query, $viewer),
                'votes' => fn (Relation $query) => $this->onlyViewer($query, $viewer),
            ])
            ->orderBy('id')
            ->limit(self::LIMIT + 1)
            ->get();

        $more = $rows->count() > self::LIMIT;
        $page = $rows->take(self::LIMIT)->values();

        return [
            'arguments' => $page,
            'latest' => (int) ($page->last()->id ?? $after),
            'more' => $more,
        ];
    }

    /**
     * @return array{arguments: Collection<int, Argument>, latest: int, more: bool}
     */
    public function across(int $after, ?User $viewer): array
    {
        $rows = Argument::query()
            ->where('id', '>', $after)
            ->with([
                'user:id,nick',
                'reactions' => fn (Relation $query) => $this->onlyViewer($query, $viewer),
                'votes' => fn (Relation $query) => $this->onlyViewer($query, $viewer),
            ])
            ->orderBy('id')
            ->limit(self::LIMIT + 1)
            ->get();

        $more = $rows->count() > self::LIMIT;
        $page = $rows->take(self::LIMIT)->values();

        return [
            'arguments' => $page,
            'latest' => (int) ($page->last()->id ?? $after),
            'more' => $more,
        ];
    }

    private function onlyViewer(Relation $query, ?User $viewer): void
    {
        if ($viewer === null) {
            $query->whereKey([]);

            return;
        }

        $query->where('user_id', $viewer->id);
    }
}
