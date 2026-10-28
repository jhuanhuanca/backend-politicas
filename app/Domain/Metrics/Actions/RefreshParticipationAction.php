<?php

namespace App\Domain\Metrics\Actions;

use App\Models\Argument;
use App\Models\ArgumentVote;
use App\Models\LeaderCandidate;
use App\Models\PollVote;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\Topic;
use App\Models\TopicMetric;
use App\Models\User;
use App\Models\UserMetric;

class RefreshParticipationAction
{
    public const CANDIDATE_PARTICIPATION = 8;

    public const CANDIDATE_RESPECT = 80;

    /**
     * @return array<string, bool|int>
     */
    public function execute(User $user): array
    {
        $this->refreshTopics();

        $consented = $user->consents()
            ->where('type', 'metrics')
            ->whereNotNull('granted_at')
            ->exists();

        if (! $consented) {
            UserMetric::query()->where('user_id', $user->id)->delete();
            LeaderCandidate::query()->where('user_id', $user->id)->delete();

            return ['consent' => false];
        }

        $debates = Topic::query()->where('user_id', $user->id)->count();
        $arguments = Argument::query()->where('user_id', $user->id)->count();
        $votes = PollVote::query()->where('user_id', $user->id)->count();
        $argumentVotes = ArgumentVote::query()->where('user_id', $user->id)->count();
        $reactions = Reaction::query()->where('user_id', $user->id)->count();
        $quality = (int) Argument::query()->where('user_id', $user->id)->sum('favor_count');
        $influence = (int) Topic::query()->where('user_id', $user->id)->sum('likes_count')
            + (int) Topic::query()->where('user_id', $user->id)->sum('shares_count')
            + (int) Argument::query()->where('user_id', $user->id)->sum('likes_count');
        $participation = ($debates * 3) + ($arguments * 2) + ($votes * 2) + $argumentVotes + $reactions;
        $respect = $this->respect($user);

        UserMetric::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'debates_count' => $debates,
                'arguments_count' => $arguments,
                'poll_votes_count' => $votes,
                'argument_votes_count' => $argumentVotes,
                'reactions_count' => $reactions,
                'participation' => $participation,
                'quality' => $quality,
                'respect' => $respect,
                'influence' => $influence,
                'calculated_at' => now(),
            ],
        );

        $candidate = $participation >= self::CANDIDATE_PARTICIPATION
            && $respect >= self::CANDIDATE_RESPECT;

        if ($candidate) {
            $status = LeaderCandidate::query()->where('user_id', $user->id)->value('status');

            LeaderCandidate::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'participation' => $participation,
                    'respect' => $respect,
                    'status' => in_array($status, ['pending', 'accepted', 'set_aside'], true)
                        ? $status
                        : 'pending',
                    'calculated_at' => now(),
                ],
            );
        } else {
            LeaderCandidate::query()->where('user_id', $user->id)->delete();
        }

        return [
            'consent' => true,
            'debates' => $debates,
            'arguments' => $arguments,
            'votes' => $votes,
            'argument_votes' => $argumentVotes,
            'reactions' => $reactions,
            'participation' => $participation,
            'quality' => $quality,
            'respect' => $respect,
            'influence' => $influence,
            'candidate' => $candidate,
        ];
    }

    private function respect(User $user): int
    {
        $topicIds = Topic::query()->where('user_id', $user->id)->pluck('id');
        $argumentIds = Argument::query()->where('user_id', $user->id)->pluck('id');

        $reports = Report::query()
            ->where('status', 'open')
            ->where(function ($query) use ($topicIds, $argumentIds): void {
                $query->where(function ($topics) use ($topicIds): void {
                    $topics->where('reportable_type', Topic::class)
                        ->whereIn('reportable_id', $topicIds);
                })->orWhere(function ($arguments) use ($argumentIds): void {
                    $arguments->where('reportable_type', Argument::class)
                        ->whereIn('reportable_id', $argumentIds);
                });
            })
            ->count();

        return max(0, 100 - ($reports * 20));
    }

    private function refreshTopics(): void
    {
        Topic::query()->orderBy('id')->each(function (Topic $topic): void {
            $arguments = Argument::query()
                ->where('topic_id', $topic->id)
                ->get(['id', 'parent_id', 'favor_count', 'contra_count']);
            $argumentIds = $arguments->pluck('id');

            $argumentReactions = $argumentIds->isEmpty()
                ? 0
                : Reaction::query()
                    ->where('reactable_type', Argument::class)
                    ->whereIn('reactable_id', $argumentIds)
                    ->count();

            TopicMetric::query()->updateOrCreate(
                ['topic_id' => $topic->id],
                [
                    'arguments_count' => $arguments->whereNull('parent_id')->count(),
                    'replies_count' => $arguments->whereNotNull('parent_id')->count(),
                    'reactions_count' => Reaction::query()
                        ->where('reactable_type', Topic::class)
                        ->where('reactable_id', $topic->id)
                        ->count() + $argumentReactions,
                    'favor_count' => (int) $arguments->sum('favor_count'),
                    'contra_count' => (int) $arguments->sum('contra_count'),
                    'shares_count' => $topic->shares_count,
                    'calculated_at' => now(),
                ],
            );
        });
    }
}
