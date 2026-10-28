<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\DeleteAccountAction;
use App\Domain\Identity\Actions\ShowAccountStandingAction;
use App\Domain\Identity\Actions\UpdateAvatarAction;
use App\Domain\Identity\Actions\UpdateCoverAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\DeleteAccountRequest;
use App\Http\Requests\Identity\UpdateAvatarRequest;
use App\Http\Requests\Identity\UpdateCoverRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class AccountController extends Controller
{
    public function standing(Request $request, ShowAccountStandingAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $action->execute($user),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function avatar(UpdateAvatarRequest $request, UpdateAvatarAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $file = $request->file('avatar');
        if (! $file instanceof UploadedFile) {
            abort(422);
        }

        $user = $action->execute($user, $file);

        return response()->json([
            'data' => (new UserResource($user))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function cover(UpdateCoverRequest $request, UpdateCoverAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $file = $request->file('cover');
        if (! $file instanceof UploadedFile) {
            abort(422);
        }

        $user = $action->execute($user, $file);

        return response()->json([
            'data' => (new UserResource($user))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    public function destroy(DeleteAccountRequest $request, DeleteAccountAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->execute($user, $request->string('nick')->toString());

        return response()->json([
            'data' => ['deleted' => true],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
