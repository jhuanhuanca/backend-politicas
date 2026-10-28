<?php

namespace App\Domain\News\Actions;

use App\Models\NewsItem;
use Illuminate\Database\Eloquent\Collection;

class ListNewsAction
{
    /**
     * @return Collection<int, NewsItem>
     */
    public function execute(): Collection
    {
        return NewsItem::query()
            ->latest('published_at')
            ->limit(30)
            ->get();
    }
}
