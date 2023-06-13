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

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Bookmark  $bookmark
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Bookmark $bookmark)
    {
        //
    }

    public function delete(User $user, Bookmark $bookmark): bool
    {
        return $this->view($user, $bookmark);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Bookmark  $bookmark
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Bookmark $bookmark)
    {
        //
    }
}
