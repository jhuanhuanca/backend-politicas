<?php

namespace App\Domain\Debates\Actions;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateDebateAction
{
    public function execute(User $author, string $body): Topic
    {
        return DB::transaction(function () use ($author, $body): Topic {
            $topic = Topic::query()->create([
                'user_id' => $author->id,
                'body' => $body,
                'context' => 'Participante',
                'kind' => 'image',
                'likes_count' => 0,
                'shares_count' => 0,
            ]);

            return $topic->load([
                'user:id,nick',
                'reactions',
                'arguments.user',
                'arguments.replies',
            ]);
        });
    }
}
