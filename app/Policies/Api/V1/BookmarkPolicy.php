<?php

namespace App\Policies\Api\V1;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookmarkPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Bookmark $bookmark): bool
    {
        return $bookmark->user()->is($user);
    }

    public function update(User $user, Bookmark $bookmark): bool
    {
        return $this->view($user, $bookmark);
    }

    public function delete(User $user, Bookmark $bookmark): bool
    {
        return $this->view($user, $bookmark);
    }
}
