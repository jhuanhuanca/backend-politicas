<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Search\Actions\SearchPublicAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function index(SearchRequest $request, SearchPublicAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute($request->string('q')->toString()),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
