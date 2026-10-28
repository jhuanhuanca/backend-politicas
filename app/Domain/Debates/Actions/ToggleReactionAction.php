<?php

namespace App\Domain\Debates\Actions;

use App\Models\Argument;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ToggleReactionAction
{
    public function __construct(private readonly EnsureDebateOpen $open) {}

    /**
     * @return array{likes: int, liked: bool}
     */
    public function execute(User $user, Topic|Argument $reactable): array
    {
        $this->open->execute($reactable);

        return DB::transaction(function () use ($user, $reactable): array {
            $reaction = $reactable->reactions()
                ->where('user_id', $user->id)
                ->where('kind', 'like')
                ->lockForUpdate()
                ->first();

            if ($reaction !== null) {
                $reaction->delete();
                if ($reactable->likes_count > 0) {
                    $reactable->decrement('likes_count');
                }

                $reactable->refresh();

                return [
                    'likes' => $reactable->likes_count,
                    'liked' => false,
                ];
            }

            $reactable->reactions()->create([
                'user_id' => $user->id,
                'kind' => 'like',
            ]);
            $reactable->increment('likes_count');
            $reactable->refresh();

            return [
                'likes' => $reactable->likes_count,
                'liked' => true,
            ];
        });
    }
}
