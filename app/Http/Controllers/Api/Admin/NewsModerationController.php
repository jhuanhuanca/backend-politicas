<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Moderation\Actions\ListStaffNewsAction;
use App\Domain\Moderation\Actions\PublishStaffNewsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublishStaffNewsRequest;
use App\Http\Resources\AdminNewsResource;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;

class NewsModerationController extends Controller
{
    public function index(ListStaffNewsAction $action): JsonResponse
    {
        return response()->json([
            'data' => AdminNewsResource::collection($action->execute())->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function store(PublishStaffNewsRequest $request, PublishStaffNewsAction $action): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();
        $item = $action->execute(
            $staff,
            $request->string('title')->toString(),
            $request->string('summary')->toString(),
            $request->string('source')->toString(),
            $request->filled('debate_id') ? $request->integer('debate_id') : null,
        );

        return response()->json([
            'data' => (new AdminNewsResource($item))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ], 201);
    }
}
