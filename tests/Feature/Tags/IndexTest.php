<?php

namespace Tests\Feature\Tags;

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
    public function users_tags_are_listed_by_the_most_recent_in_a_paginated_way(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Tag::factory()
            ->count(20)
            ->for($user)
            ->state(['created_at' => now()->subDay()])
            ->create();

        $mostRecentTag = Tag::factory()
            ->for($user)
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
                            ->where('created_at', $mostRecentTag->created_at->toJson())
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
    public function unauthenticated_user_cannot_see_the_list_of_tags(): void
    {
        $response = $this->getJson(route('api.v1.tags.index'));

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /** @test */
    public function user_can_customize_the_pagination(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Tag::factory()
            ->count(20)
            ->for($user)
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
     * @dataProvider invalidPaginationParameters
     */
    public function pagination_parameters_are_validated($invalidParameters, $errors): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->getJson(route('api.v1.tags.index', $invalidParameters));

        $response->assertUnprocessable()
            ->assertInvalid($errors);

        // $response = $this->getJson(route('api.v1.tags.index', [
        //     'page' => '5ju',
        //     'per_page' => '6fd'
        // ]));

        // $response->assertUnprocessable()
        //     ->assertInvalid([
        //         'per_page' => [
        //             'The per page must be a number.',
        //             'The per page must be an integer.'
        //         ],
        //         'page' => [
        //             'The page must be a number.',
        //             'The page must be an integer.'
        //         ],
        //     ]);

        // $response = $this->getJson(route('api.v1.tags.index', [
        //     'page' => 5.2,
        //     'per_page' => 4.5
        // ]));

        // $response->assertUnprocessable()
        //     ->assertInvalid([
        //         'per_page' => 'The per page must be an integer.',
        //         'page' => 'The page must be an integer.',
        //     ]);

        // $response = $this->getJson(route('api.v1.tags.index', [
        //     'page' => -5,
        //     'per_page' => -2
        // ]));

        // $response->assertUnprocessable()
        //     ->assertInvalid([
        //         'per_page' => 'The per page must be at least 1.',
        //         'page' => 'The page must be at least 1.',
        //     ]);

        // $response = $this->getJson(route('api.v1.tags.index', [
        //     'per_page' => 101
        // ]));

        // $response->assertUnprocessable()
        //     ->assertInvalid([
        //         'per_page' => 'The per page must not be greater than 100.'
        //     ]);
    }

    public function invalidPaginationParameters(): array
    {
        return [
            'per & per_page are strings' => [['page' => '5ju', 'per_page' => '6fd'], [
                'per_page' => [
                    'The per page must be a number.',
                    'The per page must be an integer.',
                ],
                'page' => [
                    'The page must be a number.',
                    'The page must be an integer.',
                ],
            ]],

            'per & per_page are floats' => [['page' => 5.2, 'per_page' => 4.5], [
                'per_page' => 'The per page must be an integer.',
                'page' => 'The page must be an integer.',
            ]],

            'per & per_page are less than 1' => [['page' => -5, 'per_page' => -2], [
                'per_page' => 'The per page must be at least 1.',
                'page' => 'The page must be at least 1.',
            ]],

            'per_page is greater than 100' => [['per_page' => 101], [
                'per_page' => 'The per page must not be greater than 100.',
            ]],
        ];
    }
}
