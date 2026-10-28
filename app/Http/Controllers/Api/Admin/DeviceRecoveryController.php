<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Identity\Actions\ReleaseDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReleaseDeviceRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DeviceRecoveryController extends Controller
{
    public function release(ReleaseDeviceRequest $request, ReleaseDeviceAction $action): JsonResponse
    {
        $user = User::query()
            ->where('nick', $request->string('nick')->toString())
            ->first();

        if ($user === null) {
            return response()->json([
                'data' => (object) [],
                'meta' => (object) [],
                'errors' => [
                    'user' => ['No encontramos esa cuenta.'],
                ],
            ], 404);
        }

        /** @var Staff $staff */
        $staff = $request->user();
        $released = $action->execute($staff, $user);

        return response()->json([
            'data' => (new AdminUserResource($released))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
