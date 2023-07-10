<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;
use Illuminate\Support\Collection;

class FetchOrCreateTags
{
    /**
     * @param  Collection<int, string>  $tagNames
     * @return Collection<int, Tag>
     */
    public function __invoke(Collection $tagNames): Collection
    {
        $tags = $tagNames
            ->map(
                fn (string $tagName) => strtolower(
                    (string) preg_replace('/\s+/', ' ', $tagName)  // Condense inner spaces into a single one
                )
            )
            ->unique()
            ->map(
                fn (string $tagName) => Tag::firstOrCreate(
                    ['name' => $tagName]
                )
            );

        return $tags;
    }
}
