<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Metrics\Actions\PublicMetricsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function show(PublicMetricsAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
