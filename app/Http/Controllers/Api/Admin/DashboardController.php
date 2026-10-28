<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Moderation\Actions\ListUsersAction;
use App\Domain\Moderation\Actions\ShowDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function show(ShowDashboardAction $dashboard, ListUsersAction $users): JsonResponse
    {
        return response()->json([
            'data' => [
                'counts' => $dashboard->execute(),
                'users' => AdminUserResource::collection($users->execute())->resolve(),
            ],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
