<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Metrics\Actions\UpdateMetricsConsentAction;
use App\Domain\Metrics\Jobs\RefreshParticipationMetrics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Metrics\UpdateMetricsConsentRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class ParticipationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->envelope(Bus::dispatchNow(new RefreshParticipationMetrics($user->id)));
    }

    public function consent(UpdateMetricsConsentRequest $request, UpdateMetricsConsentAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->execute($user, $request->boolean('consent_metrics'));

        return $this->envelope(Bus::dispatchNow(new RefreshParticipationMetrics($user->id)));
    }

    /**
     * @param  array<string, bool|int>  $data
     */
    private function envelope(array $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
