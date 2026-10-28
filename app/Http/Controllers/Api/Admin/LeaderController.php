<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Metrics\Actions\DecideLeaderCandidateAction;
use App\Domain\Metrics\Actions\ListLeaderCandidatesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideLeaderRequest;
use App\Http\Resources\AdminLeaderResource;
use App\Models\LeaderCandidate;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;

class LeaderController extends Controller
{
    public function index(ListLeaderCandidatesAction $action): JsonResponse
    {
        return response()->json([
            'data' => AdminLeaderResource::collection($action->execute())->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function decide(DecideLeaderRequest $request, LeaderCandidate $leaderCandidate, DecideLeaderCandidateAction $action): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();
        $candidate = $action->execute($staff, $leaderCandidate, $request->string('status')->toString());

        return response()->json([
            'data' => (new AdminLeaderResource($candidate))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
