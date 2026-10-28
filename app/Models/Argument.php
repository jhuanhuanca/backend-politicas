<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Argument extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'topic_id',
        'user_id',
        'parent_id',
        'body',
        'likes_count',
        'favor_count',
        'contra_count',
    ];

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Argument, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Argument::class, 'parent_id');
    }

    /**
     * @return HasMany<Argument, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Argument::class, 'parent_id');
    }

    /**
     * @return HasMany<ArgumentVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ArgumentVote::class);
    }

    /**
     * @return MorphMany<Reaction, $this>
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }
}
