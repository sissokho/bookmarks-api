<?php

namespace Tests\Feature\Http\Tags\Controllers;

use App\Enums\Order;
use App\Models\Bookmark;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaggedBookmarkTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_must_be_authenticated(): void
    {
        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => 'tag-slug']));

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_cannot_access_a_tag_that_does_not_exist(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => 'tag-slug']));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_access_a_tag_that_is_not_associated_to_his_bookmarks(): void
    {
        $user = User::factory()->create();

        Tag::factory()
            ->has(Bookmark::factory()) // Bookmark Belongs to another user
            ->create(['name' => strtolower('Tag One')]);

        Tag::factory()
            ->has(Bookmark::factory()->for($user)) // Bookmarks Belongs to the currently logged in user
            ->create(['name' => strtolower('Tag Two')]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => 'tag-one']));

        $response->assertUnprocessable();
    }

    /** @test */
    public function bookmarks_are_listed_by_the_most_recent_in_a_paginated_way(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $tag = Tag::factory()
            ->has(
                Bookmark::factory()
                    ->count(20)
                    ->for($user)
                    ->state(['created_at' => now()->subMinute()])
            )
            ->create(['name' => strtolower('Tag One')]);

        $mostRecentBookmark = Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->create();

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => 'tag-one']));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 15)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', $mostRecentBookmark->id)
                            ->where('title', $mostRecentBookmark->title)
                            ->where('url', $mostRecentBookmark->url)
                            ->where('favorite', $mostRecentBookmark->favorite)
                            ->where('archived', false)
                            ->where('created_at', $mostRecentBookmark->created_at->toDateTimeString())
                            ->where('tags.0.id', $tag->id)
                            ->where('tags.0.name', $tag->name)
                            ->where('tags.0.slug', $tag->slug)
                            ->where('tags.0.created_at', $tag->created_at->toDateTimeString())
                    )
            )
            ->assertJson([
                'links' => [
                    'first' => route('api.v1.tags.show-bookmarks', ['tag' => 'tag-one', 'page' => 1]),
                    'last' => route('api.v1.tags.show-bookmarks', ['tag' => 'tag-one', 'page' => 2]),
                    'prev' => null,
                    'next' => route('api.v1.tags.show-bookmarks', ['tag' => 'tag-one', 'page' => 2]),
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => 1,
                    'last_page' => 2,
                    'per_page' => 15,
                    'to' => 15,
                    'total' => 21,
                ],
            ]);
    }

    /** @test */
    public function only_users_bookmarks_are_shown(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $tag = Tag::factory()->create();

        Bookmark::factory()  // Bookmark belongs to another user
            ->hasAttached($tag)
            ->create();

        $myBlog = Bookmark::factory() // Bookmark belongs to the currently logged in user
            ->for($user)
            ->hasAttached($tag)
            ->create();

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => $tag->slug]));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 1)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', $myBlog->id)
                            ->where('title', $myBlog->title)
                            ->where('url', $myBlog->url)
                            ->where('favorite', $myBlog->favorite)
                            ->where('archived', false)
                            ->where('created_at', $myBlog->created_at->toDateTimeString())
                            ->where('tags.0.id', $tag->id)
                            ->where('tags.0.name', $tag->name)
                            ->where('tags.0.slug', $tag->slug)
                            ->where('tags.0.created_at', $tag->created_at->toDateTimeString())
                    )
            );
    }

    /** @test */
    public function favorite_bookmarks_are_returned_but_not_archived_ones(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $tag = Tag::factory()->create();

        Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->trashed()
            ->create();

        $favoriteBookmark = Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->favorite()
            ->create();

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => $tag->slug]));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 1)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', $favoriteBookmark->id)
                            ->where('title', $favoriteBookmark->title)
                            ->where('url', $favoriteBookmark->url)
                            ->where('favorite', $favoriteBookmark->favorite)
                            ->where('archived', false)
                            ->where('created_at', $favoriteBookmark->created_at->toDateTimeString())
                            ->where('tags.0.id', $tag->id)
                            ->where('tags.0.name', $tag->name)
                            ->where('tags.0.slug', $tag->slug)
                            ->where('tags.0.created_at', $tag->created_at->toDateTimeString())
                    )
            );
    }

    /** @test */
    public function user_can_customize_the_pagination(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $tag = Tag::factory()->create();

        Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->count(20)
            ->create();

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => $tag->slug, 'per_page' => 10, 'page' => 2]));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 10)
            )
            ->assertJson([
                'links' => [
                    'first' => route('api.v1.tags.show-bookmarks', ['tag' => $tag->slug, 'page' => 1]),
                    'last' => route('api.v1.tags.show-bookmarks', ['tag' => $tag->slug, 'page' => 2]),
                    'prev' => route('api.v1.tags.show-bookmarks', ['tag' => $tag->slug, 'page' => 1]),
                    'next' => null,
                ],
                'meta' => [
                    'current_page' => 2,
                    'from' => 11,
                    'last_page' => 2,
                    'per_page' => 10,
                    'to' => 20,
                    'total' => 20,
                ],
            ]);
    }

    /** @test */
    public function user_can_search_through_his_bookmarks(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $tag = Tag::factory()->create();

        Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->create(['title' => 'Laravel Website', 'url' => 'https://laravel.com']); // No matches

        $downingTech = Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->create(['title' => 'Luke Downing Blog', 'url' => 'downing.tech', 'created_at' => now()->subMinute()]); // Title and url match

        $dummyWebsite = Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->create(['title' => 'Some Dummy Website', 'url' => 'websiteisdown.com']); // url matches

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => $tag->slug, 'search' => 'down']));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 2)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', $dummyWebsite->id)
                            ->where('title', $dummyWebsite->title)
                            ->where('url', $dummyWebsite->url)
                            ->where('favorite', $dummyWebsite->favorite)
                            ->where('archived', false)
                            ->where('created_at', $dummyWebsite->created_at->toDateTimeString())
                            ->where('tags.0.id', $tag->id)
                            ->where('tags.0.name', $tag->name)
                            ->where('tags.0.slug', $tag->slug)
                            ->where('tags.0.created_at', $tag->created_at->toDateTimeString())
                    )
                    ->has(
                        'data.1',
                        fn ($json) => $json->where('id', $downingTech->id)
                            ->where('title', $downingTech->title)
                            ->where('url', $downingTech->url)
                            ->where('favorite', $downingTech->favorite)
                            ->where('archived', false)
                            ->where('created_at', $downingTech->created_at->toDateTimeString())
                            ->where('tags.0.id', $tag->id)
                            ->where('tags.0.name', $tag->name)
                            ->where('tags.0.slug', $tag->slug)
                            ->where('tags.0.created_at', $tag->created_at->toDateTimeString())
                    )
            );
    }

    /**
     * @test
     *
     * @dataProvider sortProvider
     */
    public function user_can_customize_the_ordering_of_the_bookmarks(Order $order): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $tag = Tag::factory()->create();

        $oldestBookmark = Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->create(['created_at' => now()->subHour()]);

        $newestBookmark = Bookmark::factory()
            ->for($user)
            ->hasAttached($tag)
            ->create();

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => $tag->slug, 'order_by' => $order->value]));

        $oldestBookmarkJson = [
            'id' => $oldestBookmark->id,
            'title' => $oldestBookmark->title,
            'url' => $oldestBookmark->url,
            'favorite' => $oldestBookmark->favorite,
            'archived' => false,
            'created_at' => $oldestBookmark->created_at->toDateTimeString(),
        ];

        $newestBookmarkJson = [
            'id' => $newestBookmark->id,
            'title' => $newestBookmark->title,
            'url' => $newestBookmark->url,
            'favorite' => $newestBookmark->favorite,
            'archived' => false,
            'created_at' => $newestBookmark->created_at->toDateTimeString(),
        ];

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 2)
            )
            ->assertJson([
                'data' => $order === Order::Oldest
                    ? [$oldestBookmarkJson, $newestBookmarkJson]
                    : [$newestBookmarkJson, $oldestBookmarkJson],
            ]);
    }

    /**
     * @test
     *
     * @dataProvider invalidParameters
     */
    public function parameters_are_validated($params, $errors): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Bookmark::factory()
            ->for($user)
            ->has(Tag::factory()->state(['name' => strtolower('Tag One')]))
            ->create();

        $response = $this->getJson(route('api.v1.tags.show-bookmarks', ['tag' => 'tag-one', ...$params]));

        $response->assertUnprocessable()
            ->assertInvalid($errors);
    }

    public function invalidParameters(): array
    {
        return [
            'parameters are empty' => [
                'params' => [
                    'page' => '',
                    'per_page' => '',
                    'order_by' => '',
                    'search' => '',
                ],
                'errors' => [
                    'per_page' => 'The per page field must have a value.',
                    'page' => 'The page field must have a value.',
                    'order_by' => 'The order by field must have a value.',
                    'search' => 'The search field must have a value.',
                ],
            ],

            'page & per_page are strings' => [
                'params' => [
                    'page' => '5ju',
                    'per_page' => '6fd',
                ],
                'errors' => [
                    'per_page' => [
                        'The per page must be a number.',
                        'The per page must be an integer.',
                    ],
                    'page' => [
                        'The page must be a number.',
                        'The page must be an integer.',
                    ],
                ],
            ],

            'page & per_page are floats' => [
                'params' => [
                    'page' => 5.2,
                    'per_page' => 4.5,
                ],
                'errors' => [
                    'per_page' => 'The per page must be an integer.',
                    'page' => 'The page must be an integer.',
                ],
            ],

            'page & per_page are less than 1' => [
                'params' => [
                    'page' => -5,
                    'per_page' => -2,
                ],
                'errors' => [
                    'per_page' => 'The per page must be at least 1.',
                    'page' => 'The page must be at least 1.',
                ],
            ],

            'per_page is greater than 100' => [
                'params' => [
                    'per_page' => 101,
                ],
                'errors' => [
                    'per_page' => 'The per page must not be greater than 100.',
                ],
            ],

            'sort & search are not strings' => [
                'params' => [
                    'order_by' => ['::sort'],
                    'search' => ['::search::'],
                ],
                'errors' => [
                    'order_by' => [
                        'The order_by must be a string.',
                        'The order_by value is invalid. Valid values are `newest` and `oldest`.',
                    ],
                    'search' => 'The search must be a string.',
                ],
            ],

            'sort is not in the defined values (oldest, newest)' => [
                'params' => [
                    'order_by' => 'something-else',
                ],
                'errors' => [
                    'order_by' => 'The order_by value is invalid. Valid values are `newest` and `oldest`.',
                ],
            ],
        ];
    }

    public function sortProvider(): array
    {
        return [
            'oldest first' => [Order::Oldest],
            'newest first' => [Order::Newest],
        ];
    }
}
