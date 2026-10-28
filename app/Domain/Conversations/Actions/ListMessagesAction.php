<?php

namespace App\Domain\Conversations\Actions;

use App\Models\Conversation;
use App\Models\DirectMessage;
use Illuminate\Database\Eloquent\Collection;

class ListMessagesAction
{
    private const LIMIT = 30;

    /**
     * @return array{messages: Collection<int, DirectMessage>, latest: int, more: bool}
     */
    public function execute(Conversation $conversation, int $after): array
    {
        $rows = DirectMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(self::LIMIT + 1)
            ->get();

        $more = $rows->count() > self::LIMIT;
        $page = $rows->take(self::LIMIT)->values();

        return [
            'messages' => $page,
            'latest' => (int) ($page->last()->id ?? $after),
            'more' => $more,
        ];
    }
}
