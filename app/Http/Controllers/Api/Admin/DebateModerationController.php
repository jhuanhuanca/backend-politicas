<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Moderation\Actions\ListStaffDebatesAction;
use App\Domain\Moderation\Actions\SetDebateStateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetDebateStateRequest;
use App\Http\Resources\AdminDebateResource;
use App\Models\Staff;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;

class DebateModerationController extends Controller
{
    public function index(ListStaffDebatesAction $action): JsonResponse
    {
        return response()->json([
            'data' => AdminDebateResource::collection($action->execute())->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function update(SetDebateStateRequest $request, Topic $topic, SetDebateStateAction $action): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();
        $topic = $action->execute($staff, $topic, $request->string('action')->toString());

        return response()->json([
            'data' => (new AdminDebateResource($topic))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
