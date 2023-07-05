<?php

namespace Tests\Feature\Http\Controllers\Bookmarks;

use App\Enums\Order;
use App\Models\Bookmark;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_must_be_authenticated(): void
    {
        $response = $this->getJson(route('api.v1.bookmarks.index'));

        $response->assertUnauthorized();
    }

    /** @test */
    public function bookmarks_are_listed_by_the_most_recent_in_a_paginated_way(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Bookmark::factory()
            ->count(20)
            ->for($user)
            ->create(['created_at' => now()->subDay()]);

        $mostRecentBookmark = Bookmark::factory()
            ->for($user)
            ->has(Tag::factory()->state(['name' => strtolower('Tag One')]))
            ->create();

        $response = $this->getJson(route('api.v1.bookmarks.index'));

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
                            ->where('tags.0.id', 1)
                            ->where('tags.0.name', 'tag one')
                            ->where('tags.0.slug', 'tag-one')
                            ->where('tags.0.created_at', now()->toDateTimeString())
                    )
            )
            ->assertJson([
                'links' => [
                    'first' => route('api.v1.bookmarks.index', ['page' => 1]),
                    'last' => route('api.v1.bookmarks.index', ['page' => 2]),
                    'prev' => null,
                    'next' => route('api.v1.bookmarks.index', ['page' => 2]),
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

        Bookmark::factory()->create(); // Another user's bookmark

        $userBookmark = Bookmark::factory()
            ->for($user)
            ->has(Tag::factory()->state(['name' => strtolower('Tag One')]))
            ->create();

        $response = $this->getJson(route('api.v1.bookmarks.index'));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 1)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', $userBookmark->id)
                            ->where('title', $userBookmark->title)
                            ->where('url', $userBookmark->url)
                            ->where('favorite', $userBookmark->favorite)
                            ->where('archived', false)
                            ->where('created_at', $userBookmark->created_at->toDateTimeString())
                            ->where('tags.0.id', 1)
                            ->where('tags.0.name', 'tag one')
                            ->where('tags.0.slug', 'tag-one')
                            ->where('tags.0.created_at', now()->toDateTimeString())
                    )
            );
    }

    /** @test */
    public function favorite_bookmarks_are_returned_but_not_archived_ones(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Bookmark::factory()
            ->for($user)
            ->trashed()
            ->create();

        $userBookmark = Bookmark::factory()
            ->for($user)
            ->has(Tag::factory()->state(['name' => strtolower('Tag One')]))
            ->favorite()
            ->create();

        $response = $this->getJson(route('api.v1.bookmarks.index'));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 1)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', $userBookmark->id)
                            ->where('title', $userBookmark->title)
                            ->where('url', $userBookmark->url)
                            ->where('favorite', $userBookmark->favorite)
                            ->where('archived', false)
                            ->where('created_at', $userBookmark->created_at->toDateTimeString())
                            ->where('tags.0.id', 1)
                            ->where('tags.0.name', 'tag one')
                            ->where('tags.0.slug', 'tag-one')
                            ->where('tags.0.created_at', now()->toDateTimeString())
                    )
            );
    }

    /** @test */
    public function user_can_customize_the_pagination(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Bookmark::factory()
            ->for($user)
            ->count(20)
            ->create();

        $response = $this->getJson(route('api.v1.bookmarks.index', ['per_page' => 10, 'page' => 2]));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 10)
            )
            ->assertJson([
                'links' => [
                    'first' => route('api.v1.bookmarks.index', ['page' => 1]),
                    'last' => route('api.v1.bookmarks.index', ['page' => 2]),
                    'prev' => route('api.v1.bookmarks.index', ['page' => 1]),
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

        Bookmark::factory()
            ->for($user)
            ->create(['title' => 'Laravel Website', 'url' => 'https://laravel.com']); // No matches

        $downingTech = Bookmark::factory()
            ->for($user)
            ->create(['title' => 'Luke Downing Blog', 'url' => 'downing.tech']); // Title and url match

        $dummyWebsite = Bookmark::factory()
            ->for($user)
            ->create(['title' => 'Some Dummy Website', 'url' => 'websiteisdown.com']); // url matches

        $response = $this->getJson(route('api.v1.bookmarks.index', ['search' => 'down']));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 2)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', $downingTech->id)
                            ->where('title', $downingTech->title)
                            ->where('url', $downingTech->url)
                            ->where('favorite', $downingTech->favorite)
                            ->where('archived', false)
                            ->where('created_at', $downingTech->created_at->toDateTimeString())
                            ->where('tags', [])
                    )
                    ->has(
                        'data.1',
                        fn ($json) => $json->where('id', $dummyWebsite->id)
                            ->where('title', $dummyWebsite->title)
                            ->where('url', $dummyWebsite->url)
                            ->where('favorite', $dummyWebsite->favorite)
                            ->where('archived', false)
                            ->where('created_at', $dummyWebsite->created_at->toDateTimeString())
                            ->where('tags', [])
                    )
            );
    }

    /**
     * @test
     * @dataProvider sortProvider
     */
    public function user_can_customize_the_ordering_of_the_bookmarks(Order $order): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $oldestBookmark = Bookmark::factory()
            ->for($user)
            ->create(['created_at' => now()->subHour()]);

        $newestBookmark = Bookmark::factory()
            ->for($user)
            ->create();

        $response = $this->getJson(route('api.v1.bookmarks.index', ['order_by' => $order->value]));

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
     * @dataProvider invalidParameters
     */
    public function parameters_are_validated($params, $errors): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->getJson(route('api.v1.bookmarks.index', $params));

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
