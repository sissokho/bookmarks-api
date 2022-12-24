<?php

namespace App\Http\Controllers\Api\V1\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tags\StoreRequest;
use App\Http\Resources\V1\TagResource;
use App\Models\User;
use Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request): JsonResource
    {
        /** @var User $user */
        $user = Auth::user();

        $tag = $user->tags()->create([
            'name' => strtolower(strval($request->name)),
        ]);

        return TagResource::make($tag);
    }
}
