<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Moderation\Actions\DecideReportAction;
use App\Domain\Moderation\Actions\ListNewerReportsAction;
use App\Domain\Moderation\Actions\ListReportsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideReportRequest;
use App\Http\Requests\Admin\NewerReportsRequest;
use App\Http\Resources\AdminReportResource;
use App\Models\Report;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;

class ReportQueueController extends Controller
{
    public function index(ListReportsAction $action): JsonResponse
    {
        return response()->json([
            'data' => AdminReportResource::collection($action->execute())->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function newer(NewerReportsRequest $request, ListNewerReportsAction $action): JsonResponse
    {
        $reports = $action->execute((int) $request->integer('after'));

        return response()->json([
            'data' => [
                'latest' => $action->latestOpenId(),
                'reports' => AdminReportResource::collection($reports)->resolve(),
            ],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function decide(DecideReportRequest $request, Report $report, DecideReportAction $action): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();
        $report = $action->execute($staff, $report, $request->string('status')->toString());

        return response()->json([
            'data' => (new AdminReportResource($report->load(['user:id,nick', 'reportable'])))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
