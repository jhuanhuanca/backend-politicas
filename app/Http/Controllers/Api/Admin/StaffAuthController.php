<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Moderation\Actions\ConfirmStaffTotpAction;
use App\Domain\Moderation\Actions\LoginStaffAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmStaffTotpRequest;
use App\Http\Requests\Admin\LoginStaffRequest;
use App\Models\AdminAuditLog;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class StaffAuthController extends Controller
{
    public function login(LoginStaffRequest $request, LoginStaffAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'data' => $result,
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function confirmTotp(ConfirmStaffTotpRequest $request, ConfirmStaffTotpAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->string('challenge')->toString(),
            $request->string('code')->toString(),
        );

        return response()->json([
            'data' => [
                'token' => $result['token'],
                'staff' => [
                    'name' => $result['name'],
                    'role' => $result['role'],
                ],
            ],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();

        return response()->json([
            'data' => [
                'name' => $staff->name,
                'role' => $staff->role,
            ],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();
        $token = $staff->currentAccessToken();

        AdminAuditLog::query()->create([
            'staff_id' => $staff->id,
            'action' => 'staff.logout',
            'created_at' => now(),
        ]);

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'data' => (object) [],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
