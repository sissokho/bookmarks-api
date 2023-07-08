<?php

namespace App\Policies\Api\V1;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function view(User $user, Tag $tag): bool
    {
        return $user->tags()->where('slug', $tag->slug)->exists();
    }
}
