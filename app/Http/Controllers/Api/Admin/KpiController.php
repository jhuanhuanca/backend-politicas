<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Metrics\Actions\ShowKpiAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminKpiDebateResource;
use Illuminate\Http\JsonResponse;

class KpiController extends Controller
{
    public function show(ShowKpiAction $action): JsonResponse
    {
        $kpi = $action->execute();

        return response()->json([
            'data' => [
                'platform' => $kpi['platform'],
                'scores' => $kpi['scores'],
                'debates' => AdminKpiDebateResource::collection($kpi['debates'])->resolve(),
            ],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
