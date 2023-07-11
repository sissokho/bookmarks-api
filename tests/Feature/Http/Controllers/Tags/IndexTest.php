<?php

namespace Tests\Feature\Http\Tags\Controllers;

use App\Models\Bookmark;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function unauthenticated_user_cannot_see_the_list_of_tags(): void
    {
        $response = $this->getJson(route('api.v1.tags.index'));

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /** @test */
    public function tags_are_listed_by_the_most_recent_in_a_paginated_way(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Tag::factory()
            ->count(20)
            ->has(Bookmark::factory()->for($user))
            ->state(['created_at' => now()->subDay()])
            ->create();

        $mostRecentTag = Tag::factory()
            ->has(Bookmark::factory()->for($user))
            ->create();

        $response = $this->getJson(route('api.v1.tags.index'));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 15)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', $mostRecentTag->id)
                            ->where('name', $mostRecentTag->name)
                            ->where('slug', $mostRecentTag->slug)
                            ->where('created_at', $mostRecentTag->created_at->toDateTimeString())
                    )
            )
            ->assertJson([
                'links' => [
                    'first' => route('api.v1.tags.index', ['page' => 1]),
                    'last' => route('api.v1.tags.index', ['page' => 2]),
                    'prev' => null,
                    'next' => route('api.v1.tags.index', ['page' => 2]),
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
    public function only_tags_associated_to_users_bookmarks_are_returned(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Bookmark::factory()
            ->for($user)
            ->has(
                Tag::factory()
                    ->count(2)
                    ->state(new Sequence(
                        ['name' => strtolower('Tag One'), 'created_at' => now()->subMinute()],
                        ['name' => strtolower('Tag Two')]
                    ))
            )
            ->create();

        Bookmark::factory() // Belongs to another user
            ->has(Tag::factory()->count(5))
            ->create();

        $response = $this->getJson(route('api.v1.tags.index'));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 2)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('id', 2)
                            ->where('name', 'tag two')
                            ->where('slug', 'tag-two')
                            ->etc()
                    )
                    ->has(
                        'data.1',
                        fn ($json) => $json->where('id', 1)
                            ->where('name', 'tag one')
                            ->where('slug', 'tag-one')
                            ->etc()
                    )
            );
    }

    /** @test */
    public function user_can_can_search_through_his_tags(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Bookmark::factory()
            ->for($user)
            ->has(
                Tag::factory()
                    ->count(3)
                    ->state(new Sequence(
                        ['name' => strtolower('Documentation')],
                        ['name' => strtolower('Portfolio')],
                        ['name' => strtolower('Project examples')]
                    ))
            )
            ->create();

        $response = $this->getJson(route('api.v1.tags.index', ['search' => 'p']));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 2)
                    ->has(
                        'data.0',
                        fn ($json) => $json->where('name', 'portfolio')
                            ->where('slug', 'portfolio')
                            ->etc()
                    )
                    ->has(
                        'data.1',
                        fn ($json) => $json->where('name', 'project examples')
                            ->where('slug', 'project-examples')
                            ->etc()
                    )
            );
    }

    /** @test */
    public function user_can_customize_the_pagination(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Tag::factory()
            ->count(20)
            ->has(Bookmark::factory()->for($user))
            ->create();

        $response = $this->getJson(route('api.v1.tags.index', ['per_page' => 10, 'page' => 2]));

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json->hasAll(['meta', 'links'])
                    ->has('data', 10)
            )
            ->assertJson([
                'links' => [
                    'first' => route('api.v1.tags.index', ['page' => 1]),
                    'last' => route('api.v1.tags.index', ['page' => 2]),
                    'prev' => route('api.v1.tags.index', ['page' => 1]),
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

    /**
     * @test
     *
     * @dataProvider invalidPaginationParameters
     */
    public function pagination_parameters_are_validated($params, $errors): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->getJson(route('api.v1.tags.index', $params));

        $response->assertUnprocessable()
            ->assertInvalid($errors);
    }

    public function invalidPaginationParameters(): array
    {
        return [
            'parameters are empty' => [
                'params' => [
                    'page' => '',
                    'per_page' => '',
                    'search' => '',
                ],
                'errors' => [
                    'per_page' => 'The per page field must have a value.',
                    'page' => 'The page field must have a value.',
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

            'search is not a string' => [
                'params' => [
                    'search' => ['::search::'],
                ],
                'errors' => [
                    'search' => 'The search must be a string.',
                ],
            ],
        ];
    }
}
