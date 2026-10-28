<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Moderation\Actions\ApplySanctionAction;
use App\Domain\Moderation\Actions\LiftSanctionAction;
use App\Domain\Moderation\Actions\ListSanctionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApplySanctionRequest;
use App\Http\Resources\AdminSanctionResource;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SanctionController extends Controller
{
    public function index(ListSanctionsAction $action): JsonResponse
    {
        return response()->json([
            'data' => AdminSanctionResource::collection($action->execute())->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function store(ApplySanctionRequest $request, Report $report, ApplySanctionAction $action): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();
        $sanction = $action->execute($staff, $report, $request->integer('days'));

        return response()->json([
            'data' => (new AdminSanctionResource($sanction))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function lift(Request $request, Sanction $sanction, LiftSanctionAction $action): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();
        $sanction = $action->execute($staff, $sanction);

        return response()->json([
            'data' => (new AdminSanctionResource($sanction))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
