<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\News\Actions\ListNewsAction;
use App\Domain\News\Actions\ShowNewsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\NewsItem;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function index(ListNewsAction $action): JsonResponse
    {
        return $this->envelope(NewsResource::collection($action->execute())->resolve());
    }

    public function show(NewsItem $newsItem, ShowNewsAction $action): JsonResponse
    {
        return $this->envelope((new NewsResource($action->execute($newsItem)))->resolve());
    }

    private function envelope(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) [],
            'errors' => (object) [],
        ], $status);
    }
}
