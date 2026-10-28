<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicMetric extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'topic_id',
        'arguments_count',
        'replies_count',
        'reactions_count',
        'favor_count',
        'contra_count',
        'shares_count',
        'calculated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
