<?php

namespace App\Domain\Polls\Actions;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CastPollVoteAction
{
    public function execute(User $user, Poll $poll, int $optionId): Poll
    {
        $option = PollOption::query()
            ->where('poll_id', $poll->id)
            ->whereKey($optionId)
            ->first();

        if ($option === null) {
            throw ValidationException::withMessages([
                'option_id' => 'Esa opción no pertenece a esta votación.',
            ]);
        }

        try {
            DB::transaction(function () use ($user, $poll, $option): void {
                $existing = PollVote::query()
                    ->where('user_id', $user->id)
                    ->where('poll_id', $poll->id)
                    ->lockForUpdate()
                    ->exists();

                if ($existing) {
                    $this->alreadyVoted();
                }

                PollVote::query()->create([
                    'user_id' => $user->id,
                    'poll_id' => $poll->id,
                    'poll_option_id' => $option->id,
                ]);
                $option->increment('votes_count');
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }

            $this->alreadyVoted();
        }

        return $poll->load([
            'options',
            'votes' => fn ($query) => $query->where('user_id', $user->id),
        ]);
    }

    private function alreadyVoted(): never
    {
        throw ValidationException::withMessages([
            'option_id' => 'Ya registraste tu voto en esta propuesta.',
        ]);
    }
}
