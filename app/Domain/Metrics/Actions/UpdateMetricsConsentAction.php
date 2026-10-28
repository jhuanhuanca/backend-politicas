<?php

namespace App\Domain\Metrics\Actions;

use App\Models\User;

class UpdateMetricsConsentAction
{
    public function execute(User $user, bool $granted): void
    {
        $user->consents()->updateOrCreate(
            ['type' => 'metrics'],
            ['granted_at' => $granted ? now() : null],
        );
    }
}
