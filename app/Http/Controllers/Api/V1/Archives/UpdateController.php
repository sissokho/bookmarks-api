<?php

namespace App\Http\Controllers\Api\V1\Archives;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BookmarkResource;
use App\Models\Bookmark;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class UpdateController extends Controller
{
    public function __invoke(Bookmark $bookmark): JsonResource
    {
        $this->authorize('update', $bookmark);

        if ($bookmark->trashed()) {
            throw ValidationException::withMessages([
                'bookmark' => 'This bookmark has already been added to the archives.',
            ]);
        }

        $bookmark->delete();

        return BookmarkResource::make(
            $bookmark->load('tags')
        );
    }
}
