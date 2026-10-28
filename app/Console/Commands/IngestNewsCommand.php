<?php

namespace App\Console\Commands;

use App\Domain\News\Actions\IngestNewsFeedsAction;
use Illuminate\Console\Command;

class IngestNewsCommand extends Command
{
    protected $signature = 'news:ingest';

    protected $description = 'Lee las fuentes RSS activas y guarda las noticias nuevas.';

    public function handle(IngestNewsFeedsAction $action): int
    {
        $created = $action->execute();
        $this->info("Se guardaron {$created} noticias nuevas.");

        return self::SUCCESS;
    }
}
