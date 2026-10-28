<?php

namespace App\Domain\Debates\Actions;

use App\Domain\Debates\DebateRelations;
use App\Models\Topic;
use App\Models\User;

class ShowDebateAction
{
    public function __construct(private DebateRelations $relations) {}

    public function execute(Topic $topic, ?User $viewer): Topic
    {
        return $topic->load($this->relations->tree($viewer));
    }
}
