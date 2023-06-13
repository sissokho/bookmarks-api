<?php

namespace App\Http\Controllers\Api\V1\Bookmarks;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookmarkResource;
use App\Models\Bookmark;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowController extends Controller
{
    public function __invoke(Bookmark $bookmark): JsonResource
    {
        $this->authorize('view', $bookmark);

        return BookmarkResource::make(
            $bookmark->load('tags')
        );
    }
}
