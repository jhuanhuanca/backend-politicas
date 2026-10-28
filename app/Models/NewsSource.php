<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsSource extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'feed_url',
        'topic_id',
        'active',
        'last_fetched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * @return HasMany<NewsItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }
}
