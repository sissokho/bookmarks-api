<?php

namespace App\Http\Controllers\Api\V1\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PaginationRequest;
use App\Http\Resources\V1\TagResource;
use App\Models\User;
use Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IndexController extends Controller
{
    public function __invoke(PaginationRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = Auth::user();

        $perPage = $request->per_page ?? 15;
        $page = $request->page ?? 1;

        $tags = $user->tags()
            ->latest()
            ->paginate(
                perPage: intval($perPage),
                page: intval($page)
            );

        return TagResource::collection($tags);
    }
}
