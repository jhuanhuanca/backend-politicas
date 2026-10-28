<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function show(): JsonResponse
    {
        $document = json_decode(
            (string) file_get_contents(resource_path('openapi/v1.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        return response()->json($document);
    }
}
