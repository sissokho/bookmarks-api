<?php

namespace App\Http\Controllers\Api\V1\Archives;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookmarkResource;
use App\Models\Bookmark;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class UpdateController extends Controller
{
    public function __invoke(Bookmark $bookmark): JsonResource
    {
        $this->authorize('update', $bookmark);

        if ($bookmark->trashed()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'This bookmark has already been added to the archives.');
        }

        $bookmark->archive();

        return BookmarkResource::make(
            $bookmark->load('tags')
        );
    }
}
