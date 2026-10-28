<?php

namespace App\Domain\News\Jobs;

use App\Domain\News\Actions\IngestNewsFeedsAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IngestNewsFeeds implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('news');
    }

    public function handle(IngestNewsFeedsAction $action): int
    {
        return $action->execute();
    }
}
