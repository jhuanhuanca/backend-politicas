<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\LoginUserAction;
use App\Domain\Identity\Actions\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $result = $action->execute($request);

        return $this->tokenResponse($result['user'], $result['token'], 201);
    }

    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action->execute($request);

        return $this->tokenResponse($result['user'], $result['token'], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()?->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        return response()->json([
            'data' => (object) [],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    private function tokenResponse(User $user, string $token, int $status): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'meta' => (object) [],
            'errors' => (object) [],
        ], $status);
    }
}
