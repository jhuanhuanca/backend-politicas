<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityNoticeResource;
use App\Models\ActivityNotice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityNoticeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notices = ActivityNotice::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();

        return $this->envelope(
            ActivityNoticeResource::collection($notices)->resolve(),
            $this->unread($user),
        );
    }

    public function read(Request $request, ActivityNotice $notice): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($notice->user_id !== $user->id) {
            abort(404);
        }

        if ($notice->read_at === null) {
            $notice->update(['read_at' => now()]);
        }

        return $this->envelope([
            'id' => $notice->id,
            'read' => true,
        ], $this->unread($user));
    }

    private function unread(User $user): int
    {
        return ActivityNotice::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    private function envelope(mixed $data, int $unread): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => ['unread' => $unread],
            'errors' => (object) [],
        ]);
    }
}
