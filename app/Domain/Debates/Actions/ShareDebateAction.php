<?php

namespace App\Domain\Debates\Actions;

use App\Models\Topic;
use Illuminate\Support\Facades\DB;

class ShareDebateAction
{
    public function execute(Topic $topic): Topic
    {
        return DB::transaction(function () use ($topic): Topic {
            $topic->increment('shares_count');

            return $topic->refresh();
        });
    }
}
