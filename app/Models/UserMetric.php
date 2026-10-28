<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMetric extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'debates_count',
        'arguments_count',
        'poll_votes_count',
        'argument_votes_count',
        'reactions_count',
        'participation',
        'quality',
        'respect',
        'influence',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
