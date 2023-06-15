<?php

namespace App\Http\Controllers\Api\V1\Favorites;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookmarkResource;
use App\Models\Bookmark;
use Illuminate\Http\Resources\Json\JsonResource;

class DestroyController extends Controller
{
    public function __invoke(Bookmark $bookmark): JsonResource
    {
        $this->authorize('update', $bookmark);

        $bookmark->fill(['favorite' => false]);

        if ($bookmark->isDirty()) {
            $bookmark->save();
        }

        return BookmarkResource::make(
            $bookmark->load('tags')
        );
    }
}