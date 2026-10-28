<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Moderation\Actions\CreateReportAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, CreateReportAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $body = $request->filled('body') ? $request->string('body')->toString() : null;

        $report = $action->execute(
            $user,
            $request->string('target_type')->toString(),
            $request->integer('target_id'),
            $request->string('reason')->toString(),
            $body,
        );

        return response()->json([
            'data' => (new ReportResource($report))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ], 201);
    }
}
