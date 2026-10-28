<?php

namespace App\Domain\Moderation\Actions;

use App\Models\Report;
use App\Models\Topic;
use App\Models\User;

class ShowDashboardAction
{
    /**
     * @return array{users: int, open_reports: int, debates: int}
     */
    public function execute(): array
    {
        return [
            'users' => User::query()->count(),
            'open_reports' => Report::query()->where('status', 'open')->count(),
            'debates' => Topic::query()->count(),
        ];
    }
}
