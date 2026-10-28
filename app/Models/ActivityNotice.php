<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityNotice extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'author_nick',
        'kind',
        'debate_id',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function debate(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'debate_id');
    }
}
