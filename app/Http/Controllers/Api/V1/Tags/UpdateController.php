<?php

namespace App\Http\Controllers\Api\V1\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tags\UpdateRequest;
use App\Http\Resources\V1\TagResource;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Tag $tag): JsonResource
    {
        $this->authorize('update', $tag);

        $tag->fill($request->safe()->all());

        if ($tag->isDirty()) {
            $tag->save();
        }

        return TagResource::make($tag);
    }
}
