<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Tag $tag): bool
    {
        return $tag->user()->is($user);
    }

    public function update(User $user, Tag $tag): bool
    {
        return $this->view($user, $tag);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $this->view($user, $tag);
    }
}
