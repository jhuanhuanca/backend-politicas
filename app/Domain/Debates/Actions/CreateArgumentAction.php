<?php

namespace App\Domain\Debates\Actions;

use App\Models\ActivityNotice;
use App\Models\Argument;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateArgumentAction
{
    public function __construct(private readonly EnsureDebateOpen $open) {}

    public function execute(User $author, Topic $topic, string $body, ?int $parentId = null): Argument
    {
        $this->open->execute($topic);
        $parent = $this->parent($topic, $parentId);

        return DB::transaction(function () use ($author, $topic, $body, $parent): Argument {
            $argument = Argument::query()->create([
                'topic_id' => $topic->id,
                'user_id' => $author->id,
                'parent_id' => $parent?->id,
                'body' => $body,
                'likes_count' => 0,
                'favor_count' => 0,
                'contra_count' => 0,
            ]);

            $this->notify($author, $topic, $parent);

            return $argument->load([
                'user:id,nick,avatar',
                'reactions',
                'votes',
                'replies',
            ]);
        });
    }

    private function parent(Topic $topic, ?int $parentId): ?Argument
    {
        if ($parentId === null) {
            return null;
        }

        $parent = Argument::query()->find($parentId);

        if ($parent === null || (int) $parent->topic_id !== (int) $topic->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Esa respuesta no pertenece a este debate.',
            ]);
        }

        if ($parent->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Responde al comentario principal del hilo.',
            ]);
        }

        return $parent;
    }

    private function notify(User $author, Topic $topic, ?Argument $parent): void
    {
        $recipientId = $parent?->user_id ?? $topic->user_id;

        if ((int) $recipientId === (int) $author->id) {
            return;
        }

        ActivityNotice::query()->create([
            'user_id' => $recipientId,
            'author_nick' => $author->nick,
            'kind' => $parent === null ? 'argument' : 'reply',
            'debate_id' => $topic->id,
        ]);
    }
}
