<?php

namespace App\Http\Controllers\Api\V1\Bookmarks;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DestroyController extends Controller
{
    public function __invoke(Bookmark $bookmark): JsonResponse
    {
        $this->authorize('delete', $bookmark);

        $bookmark->forceDelete();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
