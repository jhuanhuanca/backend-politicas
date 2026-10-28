<?php

namespace App\Domain\News\Actions;

use App\Models\NewsItem;

class ShowNewsAction
{
    public function execute(NewsItem $item): NewsItem
    {
        return $item;
    }
}
