<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Debates\Actions\CastArgumentVoteAction;
use App\Domain\Debates\Actions\CreateArgumentAction;
use App\Domain\Debates\Actions\CreateDebateAction;
use App\Domain\Debates\Actions\ListDebatesAction;
use App\Domain\Debates\Actions\ListNewerArgumentsAction;
use App\Domain\Debates\Actions\ListNewerDebatesAction;
use App\Domain\Debates\Actions\ShareDebateAction;
use App\Domain\Debates\Actions\ShowDebateAction;
use App\Domain\Debates\Actions\ToggleReactionAction;
use App\Domain\Identity\Services\ResolvePublicViewer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Debates\NewerCursorRequest;
use App\Http\Requests\Debates\StoreArgumentRequest;
use App\Http\Requests\Debates\StoreArgumentVoteRequest;
use App\Http\Requests\Debates\StoreDebateRequest;
use App\Http\Resources\ArgumentResource;
use App\Http\Resources\DebateResource;
use App\Models\Argument;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class DebateController extends Controller
{
    public function index(Request $request, ListDebatesAction $action, ResolvePublicViewer $viewers): JsonResponse
    {
        $topics = $action->execute($viewers->from($request));

        return $this->envelope(DebateResource::collection($topics)->resolve());
    }

    public function show(Request $request, Topic $topic, ShowDebateAction $action, ResolvePublicViewer $viewers): JsonResponse
    {
        $topic = $action->execute($topic, $viewers->from($request));

        return $this->envelope((new DebateResource($topic))->resolve());
    }

    public function newer(NewerCursorRequest $request, ListNewerDebatesAction $action): JsonResponse
    {
        $page = $action->execute((int) $request->input('after', 0));

        return $this->envelope(
            DebateResource::collection($page['topics'])->resolve(),
            meta: [
                'latest' => $page['latest'],
                'more' => $page['more'],
            ],
        );
    }

    public function newerArguments(
        NewerCursorRequest $request,
        ListNewerArgumentsAction $action,
        ResolvePublicViewer $viewers,
    ): JsonResponse {
        $page = $action->across((int) $request->input('after', 0), $viewers->from($request));

        return $this->envelope(
            ArgumentResource::collection($page['arguments'])->resolve(),
            meta: [
                'latest' => $page['latest'],
                'more' => $page['more'],
            ],
        );
    }

    public function updates(
        NewerCursorRequest $request,
        Topic $topic,
        ListNewerArgumentsAction $action,
        ResolvePublicViewer $viewers,
    ): JsonResponse {
        $page = $action->execute($topic, (int) $request->input('after', 0), $viewers->from($request));

        return $this->envelope(
            ArgumentResource::collection($page['arguments'])->resolve(),
            meta: [
                'latest' => $page['latest'],
                'more' => $page['more'],
            ],
        );
    }

    public function store(StoreDebateRequest $request, CreateDebateAction $action): JsonResponse
    {
        $upload = $request->file('media');

        $topic = $action->execute(
            $this->author($request),
            $request->string('body')->toString(),
            $request->filled('image') ? $request->string('image')->toString() : null,
            $upload instanceof UploadedFile ? $upload : null,
            $request->string('kind')->toString(),
        );

        return $this->envelope((new DebateResource($topic))->resolve(), 201);
    }

    public function storeArgument(StoreArgumentRequest $request, Topic $topic, CreateArgumentAction $action): JsonResponse
    {
        $argument = $action->execute(
            $this->author($request),
            $topic,
            $request->string('body')->toString(),
            $request->filled('parent_id') ? $request->integer('parent_id') : null,
        );

        return $this->envelope((new ArgumentResource($argument))->resolve(), 201);
    }

    public function react(Request $request, Topic $topic, ToggleReactionAction $action): JsonResponse
    {
        return $this->envelope($action->execute($this->author($request), $topic));
    }

    public function reactToArgument(Request $request, Argument $argument, ToggleReactionAction $action): JsonResponse
    {
        return $this->envelope($action->execute($this->author($request), $argument));
    }

    public function vote(StoreArgumentVoteRequest $request, Argument $argument, CastArgumentVoteAction $action): JsonResponse
    {
        return $this->envelope($action->execute(
            $this->author($request),
            $argument,
            $request->string('stance')->toString(),
        ));
    }

    public function share(Topic $topic, ShareDebateAction $action): JsonResponse
    {
        $topic = $action->execute($topic);

        return $this->envelope([
            'shares' => $topic->shares_count,
        ]);
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
}
