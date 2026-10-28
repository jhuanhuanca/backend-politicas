<?php

namespace App\Domain\Metrics\Jobs;

use App\Domain\Metrics\Actions\RefreshParticipationAction;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshParticipationMetrics implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    /**
     * @return array<string, bool|int>
     */
    public function handle(RefreshParticipationAction $action): array
    {
        $user = User::query()->findOrFail($this->userId);

        return $action->execute($user);
    }
}
