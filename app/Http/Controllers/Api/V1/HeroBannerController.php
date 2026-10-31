<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HeroBannerResource;
use App\Models\HeroBanner;
use Illuminate\Http\JsonResponse;

class HeroBannerController extends Controller
{
    public function index(): JsonResponse
    {
        $stored = HeroBanner::query()->get()->keyBy('slot');

        return response()->json([
            'data' => [
                'home' => $this->slot('home', $stored->get('home')),
                'polls' => $this->slot('polls', $stored->get('polls')),
            ],
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function slot(string $name, ?HeroBanner $banner): array
    {
        if ($banner === null) {
            return [
                'slot' => $name,
                'announcement' => null,
                'kind' => null,
                'media' => null,
            ];
        }

        return (new HeroBannerResource($banner))->resolve();
    }
}
