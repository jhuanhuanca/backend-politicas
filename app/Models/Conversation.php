<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'low_user_id',
        'high_user_id',
    ];

    public function includes(User $user): bool
    {
        return $this->low_user_id === $user->id || $this->high_user_id === $user->id;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lowUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'low_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function highUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'high_user_id');
    }

    /**
     * @return HasMany<DirectMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(DirectMessage::class);
    }

    /**
     * @return HasOne<DirectMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(DirectMessage::class)->latestOfMany();
    }
}
