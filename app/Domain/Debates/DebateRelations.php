<?php

namespace App\Domain\Debates;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;

class DebateRelations
{
    /**
     * @return array<string, mixed>
     */
    public function tree(?User $viewer): array
    {
        return [
            'user:id,nick',
            'reactions' => fn (Relation $query) => $this->onlyViewer($query, $viewer),
            'arguments' => function (Relation $query) use ($viewer): void {
                $query->with([
                    'user:id,nick',
                    'reactions' => fn (Relation $reactions) => $this->onlyViewer($reactions, $viewer),
                    'votes' => fn (Relation $votes) => $this->onlyViewer($votes, $viewer),
                    'replies' => function (Relation $replies) use ($viewer): void {
                        $replies->with([
                            'user:id,nick',
                            'reactions' => fn (Relation $reactions) => $this->onlyViewer($reactions, $viewer),
                            'votes' => fn (Relation $votes) => $this->onlyViewer($votes, $viewer),
                        ])->orderBy('created_at');
                    },
                ])->orderBy('created_at');
            },
        ];
    }

    private function onlyViewer(Relation $query, ?User $viewer): void
    {
        if ($viewer === null) {
            $query->whereKey([]);

            return;
        }

        $query->where('user_id', $viewer->id);
    }
}
