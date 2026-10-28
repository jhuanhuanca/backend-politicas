<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArgumentVote extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'argument_id',
        'stance',
    ];

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
    public function argument(): BelongsTo
    {
        return $this->belongsTo(Argument::class);
    }
}
