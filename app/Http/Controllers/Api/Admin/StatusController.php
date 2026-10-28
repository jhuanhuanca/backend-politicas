<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Observability\Actions\ShowStatusAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function show(ShowStatusAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
