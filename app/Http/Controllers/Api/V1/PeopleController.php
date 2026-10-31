<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\SuggestPeopleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\SuggestPeopleRequest;
use App\Http\Resources\PublicPersonResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PeopleController extends Controller
{
    public function index(SuggestPeopleRequest $request, SuggestPeopleAction $action): JsonResponse
    {
        /** @var User $viewer */
        $viewer = $request->user();

        return response()->json([
            'data' => PublicPersonResource::collection($action->execute(
                $viewer,
                $request->filled('q') ? $request->string('q')->toString() : null,
            ))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
