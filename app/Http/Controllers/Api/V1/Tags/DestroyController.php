<?php

namespace App\Http\Controllers\Api\V1\Tags;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DestroyController extends Controller
{
    public function __invoke(Request $request, Tag $tag): Response
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return response()->noContent();
    }
}
