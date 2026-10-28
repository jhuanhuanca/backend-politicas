<?php

namespace App\Domain\Identity\Actions;

use App\Models\Sanction;
use App\Models\User;

class ShowAccountStandingAction
{
    /**
     * @return array{silenced: bool, until: string|null}
     */
    public function execute(User $user): array
    {
        $sanction = Sanction::query()
            ->where('user_id', $user->id)
            ->whereNull('lifted_at')
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->first();

        return [
            'silenced' => $sanction !== null,
            'until' => $sanction?->ends_at?->format('d/m/Y'),
        ];
    }
}
