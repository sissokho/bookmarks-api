<?php

namespace App\Http\Controllers\Api\V1\Bookmarks;

use App\Actions\FetchOrCreateTags;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Bookmarks\StoreRequest;
use App\Http\Resources\V1\BookmarkResource;
use DB;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request, FetchOrCreateTags $fetchOrCreateTags): JsonResource
    {
        $bookmark = DB::transaction(function () use ($request, $fetchOrCreateTags) {
            $bookmark = $request->user()?->bookmarks()->create(
                $request->safe()->except('tags')
            );

            if ($request->filled('tags')) {
                $tags = $fetchOrCreateTags($request->collect('tags'));

                $bookmark?->tags()->attach($tags->pluck('id'));

                $bookmark?->setRelation('tags', $tags);
            }

            return $bookmark;
        });

        return BookmarkResource::make($bookmark);
    }
}
