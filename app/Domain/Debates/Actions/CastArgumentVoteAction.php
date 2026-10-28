<?php

namespace App\Domain\Debates\Actions;

use App\Models\Argument;
use App\Models\ArgumentVote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CastArgumentVoteAction
{
    public function __construct(private readonly EnsureDebateOpen $open) {}

    /**
     * @return array{favor: int, contra: int, stance: string|null}
     */
    public function execute(User $user, Argument $argument, string $stance): array
    {
        $this->open->execute($argument);

        return DB::transaction(function () use ($user, $argument, $stance): array {
            $argument = Argument::query()->whereKey($argument->id)->lockForUpdate()->firstOrFail();
            $vote = ArgumentVote::query()
                ->where('user_id', $user->id)
                ->where('argument_id', $argument->id)
                ->lockForUpdate()
                ->first();

            if ($vote !== null && $vote->stance === $stance) {
                $this->shift($argument, $stance, -1);
                $vote->delete();
                $argument->refresh();

                return $this->payload($argument, null);
            }

            if ($vote !== null) {
                $this->shift($argument, $vote->stance, -1);
                $vote->update(['stance' => $stance]);
                $this->shift($argument, $stance, 1);
                $argument->refresh();

                return $this->payload($argument, $stance);
            }

            ArgumentVote::query()->create([
                'user_id' => $user->id,
                'argument_id' => $argument->id,
                'stance' => $stance,
            ]);
            $this->shift($argument, $stance, 1);
            $argument->refresh();

            return $this->payload($argument, $stance);
        });
    }

    private function shift(Argument $argument, string $stance, int $delta): void
    {
        $column = $stance === 'favor' ? 'favor_count' : 'contra_count';

        if ($delta < 0 && (int) $argument->{$column} === 0) {
            return;
        }

        if ($delta < 0) {
            $argument->decrement($column);

            return;
        }

        $argument->increment($column);
    }

    /**
     * @return array{favor: int, contra: int, stance: string|null}
     */
    private function payload(Argument $argument, ?string $stance): array
    {
        return [
            'favor' => $argument->favor_count,
            'contra' => $argument->contra_count,
            'stance' => $stance,
        ];
    }
}
