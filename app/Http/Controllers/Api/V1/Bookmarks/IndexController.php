<?php

namespace App\Http\Controllers\Api\V1\Bookmarks;

use App\Enums\Order;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Bookmarks\IndexRequest;
use App\Http\Resources\V1\BookmarkResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IndexController extends Controller
{
    public function __invoke(IndexRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $perPage = $request->integer('per_page', default: 15);
        $page = $request->integer('page', default: 1);

        $bookmarks = $user->bookmarks()
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