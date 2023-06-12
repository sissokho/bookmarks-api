<?php

namespace App\Http\Resources\V1;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/** @mixin \App\Models\Bookmark */
class BookmarkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>|\Illuminate\Contracts\Support\Arrayable<string, mixed>|\JsonSerializable
     */
    public function toArray($request): array|Arrayable|JsonSerializable
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'favorite' => $this->favorite,
            'archived' => is_null($this->deleted_at) ? false : true,
            'created_at' => $this->created_at?->toDateTimeString(),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
