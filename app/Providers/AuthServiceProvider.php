<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Bookmark;
use App\Models\Tag;
use App\Policies\Api\V1\BookmarkPolicy;
use App\Policies\Api\V1\TagPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Bookmark::class => BookmarkPolicy::class,
        Tag::class => TagPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
