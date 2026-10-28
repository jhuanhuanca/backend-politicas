<?php

namespace App\Domain\Search\Actions;

use App\Models\Argument;
use App\Models\NewsItem;
use App\Models\Poll;
use App\Models\Topic;
use Illuminate\Support\Str;

class SearchPublicAction
{
    /**
     * @return array{
     *     debates: list<array{id: int, body: string, author_nick: string|null}>,
     *     news: list<array{id: int, title: string, source: string}>,
     *     polls: list<array{id: int, title: string, place: string}>
     * }
     */
    public function execute(string $term): array
    {
        $like = '%'.$this->literal($term).'%';

        return [
            'debates' => $this->debates($like),
            'news' => $this->news($like),
            'polls' => $this->polls($like),
        ];
    }

    private function literal(string $term): string
    {
        return str_replace(['%', '_'], '', trim($term));
    }

    /**
     * @return list<array{id: int, body: string, author_nick: string|null}>
     */
    private function debates(string $like): array
    {
        $topicIds = Topic::query()
            ->where(function ($query) use ($like): void {
                $query->where('body', 'like', $like)
                    ->orWhere('context', 'like', $like);
            })
            ->pluck('id');

        $argumentTopicIds = Argument::query()
            ->where('body', 'like', $like)
            ->pluck('topic_id');

        $ids = $topicIds->merge($argumentTopicIds)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Topic::query()
            ->with('user:id,nick')
            ->whereIn('id', $ids)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Topic $topic): array => [
                'id' => $topic->id,
                'body' => Str::limit((string) $topic->body, 160),
                'author_nick' => $topic->user?->nick,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, title: string, source: string}>
     */
    private function news(string $like): array
    {
        return NewsItem::query()
            ->where(function ($query) use ($like): void {
                $query->where('title', 'like', $like)
                    ->orWhere('summary', 'like', $like);
            })
            ->latest('published_at')
            ->limit(8)
            ->get()
            ->map(fn (NewsItem $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'source' => $item->source,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, title: string, place: string}>
     */
    private function polls(string $like): array
    {
        return Poll::query()
            ->where(function ($query) use ($like): void {
                $query->where('title', 'like', $like)
                    ->orWhere('place', 'like', $like);
            })
            ->orderBy('position')
            ->limit(8)
            ->get()
            ->map(fn (Poll $poll): array => [
                'id' => $poll->id,
                'title' => $poll->title,
                'place' => $poll->place,
            ])
            ->values()
            ->all();
    }
}
