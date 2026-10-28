<?php

namespace App\Domain\Identity\Actions;

use App\Models\Argument;
use App\Models\ArgumentVote;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\Topic;
use App\Models\User;
use App\Support\PublicMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteAccountAction
{
    public function execute(User $user, string $nick): void
    {
        if ($nick !== $user->nick) {
            throw ValidationException::withMessages([
                'nick' => 'Escribe tu nick para borrar la cuenta.',
            ]);
        }

        $avatar = $user->avatar;
        $cover = $user->cover;

        DB::transaction(function () use ($user): void {
            $this->releasePollVotes($user);
            $this->releaseReactions($user);
            $this->releaseArgumentVotes($user);
            $this->removeContentTraces($user);
            $user->tokens()->delete();
            $user->delete();
        });

        $media = app(PublicMedia::class);
        $media->delete(is_string($avatar) ? $avatar : null);
        $media->delete(is_string($cover) ? $cover : null);
    }

    private function releasePollVotes(User $user): void
    {
        PollVote::query()
            ->where('user_id', $user->id)
            ->each(function (PollVote $vote): void {
                $this->decrement(PollOption::class, $vote->poll_option_id, 'votes_count');
            });
    }

    private function releaseReactions(User $user): void
    {
        Reaction::query()
            ->where('user_id', $user->id)
            ->each(function (Reaction $reaction): void {
                if ($reaction->reactable_type === Topic::class) {
                    $this->decrement(Topic::class, $reaction->reactable_id, 'likes_count');
                }

                if ($reaction->reactable_type === Argument::class) {
                    $this->decrement(Argument::class, $reaction->reactable_id, 'likes_count');
                }
            });
    }

    private function releaseArgumentVotes(User $user): void
    {
        ArgumentVote::query()
            ->where('user_id', $user->id)
            ->each(function (ArgumentVote $vote): void {
                $column = $vote->stance === 'contra' ? 'contra_count' : 'favor_count';
                $this->decrement(Argument::class, $vote->argument_id, $column);
            });
    }

    private function removeContentTraces(User $user): void
    {
        $topicIds = Topic::query()->where('user_id', $user->id)->pluck('id');
        $argumentIds = Argument::query()->where('user_id', $user->id)->pluck('id');

        if ($topicIds->isEmpty() && $argumentIds->isEmpty()) {
            return;
        }

        Report::query()
            ->where(function ($query) use ($topicIds, $argumentIds): void {
                $query->where(function ($topics) use ($topicIds): void {
                    $topics->where('reportable_type', Topic::class)
                        ->whereIn('reportable_id', $topicIds);
                })->orWhere(function ($arguments) use ($argumentIds): void {
                    $arguments->where('reportable_type', Argument::class)
                        ->whereIn('reportable_id', $argumentIds);
                });
            })
            ->delete();

        Reaction::query()
            ->where(function ($query) use ($topicIds, $argumentIds): void {
                $query->where(function ($topics) use ($topicIds): void {
                    $topics->where('reactable_type', Topic::class)
                        ->whereIn('reactable_id', $topicIds);
                })->orWhere(function ($arguments) use ($argumentIds): void {
                    $arguments->where('reactable_type', Argument::class)
                        ->whereIn('reactable_id', $argumentIds);
                });
            })
            ->delete();
    }

    /**
     * @param  class-string<Argument|PollOption|Topic>  $model
     */
    private function decrement(string $model, int $id, string $column): void
    {
        $model::query()->whereKey($id)->where($column, '>', 0)->decrement($column);
    }
}
