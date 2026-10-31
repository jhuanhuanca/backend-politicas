<?php

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Moderation\Actions\SaveHeroBannerAction;
use App\Http\Controllers\Api\V1\HeroBannerController as PublicHeroBannerController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveHeroBannerRequest;
use App\Http\Resources\HeroBannerResource;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class HeroBannerController extends Controller
{
    public function index(PublicHeroBannerController $public): JsonResponse
    {
        return $public->index();
    }

    public function store(SaveHeroBannerRequest $request, SaveHeroBannerAction $action): JsonResponse
    {
        /** @var Staff $staff */
        $staff = $request->user();
        $file = $request->file('media');
        $banner = $action->execute(
            $staff,
            $request->string('slot')->toString(),
            $request->input('announcement'),
            $file instanceof UploadedFile ? $file : null,
            $request->filled('link') ? $request->string('link')->toString() : null,
            $request->boolean('clear'),
        );

        return response()->json([
            'data' => (new HeroBannerResource($banner))->resolve(),
            'meta' => (object) [],
            'errors' => (object) [],
        ]);
    }
}
