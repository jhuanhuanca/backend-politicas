<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Services\ResolvePublicViewer;
use App\Domain\Polls\Actions\CastPollVoteAction;
use App\Domain\Polls\Actions\ListPollsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Polls\StorePollVoteRequest;
use App\Http\Resources\PollResource;
use App\Models\Poll;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PollController extends Controller
{
    public function index(Request $request, ListPollsAction $action, ResolvePublicViewer $viewers): JsonResponse
    {
        $polls = $action->execute($viewers->from($request));

        return $this->envelope(PollResource::collection($polls)->resolve());
    }

    public function vote(StorePollVoteRequest $request, Poll $poll, CastPollVoteAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $poll = $action->execute($user, $poll, $request->integer('option_id'));

        return $this->envelope((new PollResource($poll))->resolve());
    }

    private function envelope(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) [],
            'errors' => (object) [],
        ], $status);
    }
}
