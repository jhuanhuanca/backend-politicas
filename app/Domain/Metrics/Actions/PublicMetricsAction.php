<?php

namespace App\Domain\Metrics\Actions;

use App\Models\Argument;
use App\Models\NewsItem;
use App\Models\PollVote;
use App\Models\Topic;

class PublicMetricsAction
{
    /**
     * @return array{debates: int, arguments: int, votes: int, news: int, voices: int}
     */
    public function execute(): array
    {
        $voices = Topic::query()->pluck('user_id')
            ->merge(Argument::query()->pluck('user_id'))
            ->merge(PollVote::query()->pluck('user_id'))
            ->unique()
            ->count();

        return [
            'debates' => Topic::query()->count(),
            'arguments' => Argument::query()->count(),
            'votes' => PollVote::query()->count(),
            'news' => NewsItem::query()->count(),
            'voices' => $voices,
        ];
    }
}
