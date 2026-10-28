<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Conversations\Actions\ListConversationsAction;
use App\Domain\Conversations\Actions\ListMessagesAction;
use App\Domain\Conversations\Actions\OpenConversationAction;
use App\Domain\Conversations\Actions\SendMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conversations\OpenConversationRequest;
use App\Http\Requests\Conversations\SendMessageRequest;
use App\Http\Requests\Debates\NewerCursorRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\DirectMessageResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request, ListConversationsAction $action): JsonResponse
    {
        return $this->envelope(
            ConversationResource::collection($action->execute($this->author($request)))->resolve(),
        );
    }

    public function store(OpenConversationRequest $request, OpenConversationAction $action): JsonResponse
    {
        $conversation = $action->execute($this->author($request), $request->string('nick')->toString());
        if ($conversation === null) {
            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'nick' => ['No encontramos esa cuenta.'],
                ],
            ], 404);
        }

        return $this->envelope((new ConversationResource($conversation))->resolve(), 201);
    }

    public function messages(
        NewerCursorRequest $request,
        Conversation $conversation,
        ListMessagesAction $action,
    ): JsonResponse {
        if (! $conversation->includes($this->author($request))) {
            return $this->missing();
        }

        $page = $action->execute($conversation, (int) $request->input('after', 0));

        return $this->envelope(
            DirectMessageResource::collection($page['messages'])->resolve(),
            meta: [
                'latest' => $page['latest'],
                'more' => $page['more'],
            ],
        );
    }

    public function send(
        SendMessageRequest $request,
        Conversation $conversation,
        SendMessageAction $action,
    ): JsonResponse {
        $user = $this->author($request);
        if (! $conversation->includes($user)) {
            return $this->missing();
        }

        $message = $action->execute($user, $conversation, $request->string('body')->toString());

        return $this->envelope((new DirectMessageResource($message))->resolve(), 201);
    }

    private function author(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function envelope(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta === [] ? (object) [] : $meta,
            'errors' => (object) [],
        ], $status);
    }

    private function missing(): JsonResponse
    {
        return response()->json([
            'data' => (object) [],
            'meta' => (object) [],
            'errors' => [
                'conversation' => ['No encontramos esa conversación.'],
            ],
        ], 404);
    }
}
