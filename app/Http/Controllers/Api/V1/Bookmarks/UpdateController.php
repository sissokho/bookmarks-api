<?php

namespace App\Http\Controllers\Api\V1\Bookmarks;

use App\Actions\FetchOrCreateTags;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Bookmarks\UpdateRequest;
use App\Http\Resources\V1\BookmarkResource;
use App\Models\Bookmark;
use Illuminate\Http\Resources\Json\JsonResource;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Bookmark $bookmark, FetchOrCreateTags $fetchOrCreateTags): JsonResource
    {
        $this->authorize('update', $bookmark);

        $bookmark->fill($request->only(['title', 'url', 'favorite']));

        if ($bookmark->isDirty()) {
            $bookmark->save();
        }

        if ($request->filled('tags')) {
            $tags = $fetchOrCreateTags($request->collect('tags'));

            $bookmark->tags()->sync($tags->pluck('id'));

            $bookmark->setRelation('tags', $tags);
        } else {
            $bookmark->load('tags');
        }

        return BookmarkResource::make($bookmark);
    }
}
