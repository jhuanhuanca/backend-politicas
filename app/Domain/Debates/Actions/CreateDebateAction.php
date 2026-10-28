<?php

namespace App\Domain\Debates\Actions;

use App\Models\Topic;
use App\Models\User;
use App\Support\PublicMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateDebateAction
{
    public function __construct(private readonly PublicMedia $media) {}

    public function execute(User $author, string $body, ?string $image = null, ?UploadedFile $file = null, string $kind = 'image'): Topic
    {
        return DB::transaction(function () use ($author, $body, $image, $file, $kind): Topic {
            if ($file !== null) {
                $image = $this->media->store($file, 'debates');
            }

            $topic = Topic::query()->create([
                'user_id' => $author->id,
                'body' => $body,
                'context' => 'Participante',
                'image' => $image,
                'kind' => $image === null ? 'image' : $kind,
                'likes_count' => 0,
                'shares_count' => 0,
            ]);

            return $topic->load([
                'user:id,nick,avatar',
                'reactions',
                'arguments.user',
                'arguments.replies',
            ]);
        });
    }
}
