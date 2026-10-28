<?php

namespace App\Domain\Metrics\Actions;

use App\Models\Argument;
use App\Models\Consent;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\Topic;
use App\Models\UserMetric;
use Illuminate\Support\Collection;

class ShowKpiAction
{
    public function __construct(private PublicMetricsAction $publicMetrics) {}

    /**
     * @return array{
     *     platform: array{debates: int, arguments: int, votes: int, news: int, voices: int, open_reports: int, consented: int},
     *     scores: array{profiles: int, participation: int|null, quality: int|null, respect: int|null, influence: int|null},
     *     debates: Collection<int, Topic>
     * }
     */
    public function execute(): array
    {
        $public = $this->publicMetrics->execute();

        return [
            'platform' => [
                'debates' => $public['debates'],
                'arguments' => $public['arguments'],
                'votes' => $public['votes'],
                'news' => $public['news'],
                'voices' => $public['voices'],
                'open_reports' => Report::query()->where('status', 'open')->count(),
                'consented' => Consent::query()
                    ->where('type', 'metrics')
                    ->whereNotNull('granted_at')
                    ->count(),
            ],
            'scores' => $this->scores(),
            'debates' => $this->debates(),
        ];
    }

    /**
     * @return array{profiles: int, participation: int|null, quality: int|null, respect: int|null, influence: int|null}
     */
    private function scores(): array
    {
        $profiles = UserMetric::query()->count();

        if ($profiles === 0) {
            return [
                'profiles' => 0,
                'participation' => null,
                'quality' => null,
                'respect' => null,
                'influence' => null,
            ];
        }

        $averages = UserMetric::query()
            ->selectRaw('avg(participation) as participation, avg(quality) as quality, avg(respect) as respect, avg(influence) as influence')
            ->first();

        return [
            'profiles' => $profiles,
            'participation' => (int) round((float) $averages?->participation),
            'quality' => (int) round((float) $averages?->quality),
            'respect' => (int) round((float) $averages?->respect),
            'influence' => (int) round((float) $averages?->influence),
        ];
    }

    /**
     * @return Collection<int, Topic>
     */
    private function debates(): Collection
    {
        $topics = Topic::query()
            ->with('user:id,nick')
            ->latest('id')
            ->limit(30)
            ->get();

        $ids = $topics->pluck('id');
        $arguments = $ids->isEmpty()
            ? collect()
            : Argument::query()
                ->whereIn('topic_id', $ids)
                ->get(['id', 'topic_id', 'parent_id', 'favor_count', 'contra_count']);

        $argumentIds = $arguments->pluck('id');
        $topicReactions = $ids->isEmpty()
            ? collect()
            : Reaction::query()
                ->where('reactable_type', Topic::class)
                ->whereIn('reactable_id', $ids)
                ->get(['reactable_id']);
        $argumentReactions = $argumentIds->isEmpty()
            ? collect()
            : Reaction::query()
                ->where('reactable_type', Argument::class)
                ->whereIn('reactable_id', $argumentIds)
                ->get(['reactable_id']);

        $reactionsByArgument = $argumentReactions->countBy('reactable_id');

        return $topics
            ->each(function (Topic $topic) use ($arguments, $topicReactions, $reactionsByArgument): void {
                $rows = $arguments->where('topic_id', $topic->id);
                $argumentReactionTotal = $rows->sum(
                    fn (Argument $argument): int => (int) ($reactionsByArgument[$argument->id] ?? 0),
                );

                $topic->setAttribute('arguments_total', $rows->whereNull('parent_id')->count());
                $topic->setAttribute('replies_total', $rows->whereNotNull('parent_id')->count());
                $topic->setAttribute('favor_total', (int) $rows->sum('favor_count'));
                $topic->setAttribute('contra_total', (int) $rows->sum('contra_count'));
                $topic->setAttribute(
                    'reactions_total',
                    $topicReactions->where('reactable_id', $topic->id)->count() + $argumentReactionTotal,
                );
            })
            ->sortBy([
                ['arguments_total', 'desc'],
                ['reactions_total', 'desc'],
                ['id', 'desc'],
            ])
            ->take(8)
            ->values();
    }
}
