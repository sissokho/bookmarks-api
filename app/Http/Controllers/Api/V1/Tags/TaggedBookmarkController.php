<?php

namespace App\Http\Controllers\Api\V1\Tags;

use App\Enums\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Bookmarks\IndexRequest;
use App\Http\Resources\V1\BookmarkResource;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaggedBookmarkController extends Controller
{
    public function __invoke(IndexRequest $request, Tag $tag): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->cannot('view', $tag)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'This tag is not associated to any of your bookmarks.');
        }

        $perPage = $request->integer('per_page', default: 15);
        $page = $request->integer('page', default: 1);

        $bookmarks = $tag->bookmarks()
            ->whereBelongsTo($user)
            ->with('tags')
            ->when($request->filled('search'), fn (Builder $query): Builder => $query->search($request->string('search')))
            ->when($request->filled('order_by'), fn (Builder $query): Builder => match ($request->order_by) {
                Order::Newest->value => $query->latest(),
                Order::Oldest->value => $query->oldest()
            })
            ->when($request->missing('order_by'), fn (Builder $query): Builder => $query->latest())
            ->paginate(
                perPage: $perPage,
                page: $page
            );

        return BookmarkResource::collection($bookmarks);
    }
}
