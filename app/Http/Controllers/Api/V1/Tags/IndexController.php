<?php

namespace App\Http\Controllers\Api\V1\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PaginationRequest;
use App\Http\Resources\V1\TagResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IndexController extends Controller
{
    public function __invoke(PaginationRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', default: 15);
        $page = $request->integer('page', default: 1);

        $tags = $request->user()?->tags()
            ->latest()
            ->paginate(
                perPage: $perPage,
                page: $page
            );

        return TagResource::collection($tags);
    }
}
