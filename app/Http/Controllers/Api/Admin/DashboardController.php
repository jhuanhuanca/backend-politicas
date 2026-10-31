<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Moderation\Actions\ListUsersAction;
use App\Domain\Moderation\Actions\ShowDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterStaffDirectoryRequest;
use App\Http\Resources\AdminUserResource;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function show(FilterStaffDirectoryRequest $request, ShowDashboardAction $dashboard, ListUsersAction $users): JsonResponse
    {
        return response()->json([
            'data' => [
                'counts' => $dashboard->execute(),
                'users' => AdminUserResource::collection($users->execute(
                    $request->filled('q') ? $request->string('q')->toString() : null,
                    $request->filled('department') ? $request->string('department')->toString() : null,
                    $request->filled('municipality') ? $request->string('municipality')->toString() : null,
                ))->resolve(),
            ],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
