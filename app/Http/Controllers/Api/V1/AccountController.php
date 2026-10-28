<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\DeleteAccountAction;
use App\Domain\Identity\Actions\ShowAccountStandingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\DeleteAccountRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
