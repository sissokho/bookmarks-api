<?php

namespace Tests\Feature\Http\Controllers\Bookmarks;

use App\Actions\FetchOrCreateTags;
use App\Models\Bookmark;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_must_be_authenticated(): void
    {
        $response = $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => 1]));

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_cannot_update_a_non_existing_bookmark(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => 1]));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_update_a_bookmark_that_does_not_belong_to_him(): void
    {
        $bookmark = Bookmark::factory()->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => $bookmark]));

        $response->assertForbidden();
    }

    /**
     * @test
     * @dataProvider bookmarkProvider
     */
    public function bookmark_can_be_updated_partially_or_fully(array $payload): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->has(Tag::factory()->state(['name' => strtolower('Tag One')]))
            ->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => $bookmark]), $payload);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $bookmark->id,
                    'title' => Arr::get($payload, 'title') ?? $bookmark->title,
                    'url' => Arr::get($payload, 'url') ?? $bookmark->url,
                    'favorite' => Arr::get($payload, 'favorite') ?? $bookmark->favorite,
                    'archived' => false,
                    'created_at' => $bookmark->created_at->toDateTimeString(),
                    'tags' => [
                        [
                            'id' => 1,
                            'name' => 'tag one',
                            'slug' => 'tag-one',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'title' => Arr::get($payload, 'title') ?? $bookmark->title,
            'url' => Arr::get($payload, 'url') ?? $bookmark->url,
            'favorite' => Arr::get($payload, 'favorite') ?? $bookmark->favorite,
        ]);
    }

    /**
     * @test
     * @dataProvider tagProvider
     */
    public function bookmark_tags_can_be_updated(array|null $payload, array $tagsJson, int $tagsCount): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->has(
                Tag::factory()
                    ->count(2)
                    ->state(new Sequence(
                        ['name' => strtolower('tag one')],
                        ['name' => strtolower('tag two')],
                    ))
            )
            ->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => $bookmark]), [
            'tags' => $payload,
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $bookmark->id,
                    'title' => $bookmark->title,
                    'url' => $bookmark->url,
                    'favorite' => $bookmark->favorite,
                    'archived' => false,
                    'created_at' => $bookmark->created_at->toDateTimeString(),
                    'tags' => $tagsJson,
                ],
            ]);

        $this->assertCount($tagsCount, $bookmark->tags);
    }

    /** @test */
    public function archived_bookmark_can_be_updated(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->trashed()
            ->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => $bookmark]), [
            'title' => '::title::',
            'url' => 'https://twitter.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $bookmark->id,
                    'title' => '::title::',
                    'url' => 'https://twitter.com',
                    'favorite' => false,
                    'archived' => true,
                    'created_at' => $bookmark->created_at->toDateTimeString(),
                    'tags' => null,
                ],
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'title' => '::title::',
            'url' => 'https://twitter.com',
        ]);
    }

    /** @test */
    public function createorfetchaction_is_called_by_the_controller_if_tags_are_provided_in_the_request(): void
    {
        $action = $this->spy(FetchOrCreateTags::class);

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $bookmark = Bookmark::factory()
            ->for($user)
            ->create();

        $payload = Arr::except(
            [
                ...Bookmark::factory()->make()->toArray(),
                'tags' => ['tag one'],
            ],
            'user_id'
        );

        $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => $bookmark]), $payload);

        $action->shouldHaveReceived('__invoke')->once();
    }

    /** @test */
    public function createorfetchaction_is_not_called_by_the_controller_if_tags_are_not_provided_in_the_request(): void
    {
        $action = $this->spy(FetchOrCreateTags::class);

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $bookmark = Bookmark::factory()
            ->for($user)
            ->create();

        $payload = Arr::except(
            [
                ...Bookmark::factory()->make()->toArray(),
            ],
            'user_id'
        );

        $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => $bookmark]), $payload);

        $action->shouldNotHaveReceived('__invoke');
    }

    /**
     * @test
     * @dataProvider validationProvider
     */
    public function inputs_are_validated(array $payload, string $field, string $error): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.bookmarks.update', ['bookmark' => $bookmark]), $payload);

        $response->assertInvalid([$field => $error]);
    }

    public function bookmarkProvider(): array
    {
        $defaultPayload = [
            'title' => '::title::',
            'url' => 'https://google.com',
            'favorite' => true,
        ];

        return [
            'title only' => [Arr::only($defaultPayload, 'title')],
            'url only' => [Arr::only($defaultPayload, 'url')],
            'favorite only' => [Arr::only($defaultPayload, 'favorite')],
            'title, url and favorite' => [$defaultPayload],
        ];
    }

    public function tagProvider(): array
    {
        return [
            'empty tags' => [
                'payload' => [],
                'tagsJson' => [],
                'tagsCount' => 0,
            ],
            'null tags' => [
                'payload' => null,
                'tagsJson' => [],
                'tagsCount' => 0,
            ],
            'tags added' => [
                'payload' => ['tag one', 'tag two', 'tag three'],
                'tagsJson' => [
                    [
                        'id' => 1,
                        'name' => 'tag one',
                        'slug' => 'tag-one',
                    ],
                    [
                        'id' => 2,
                        'name' => 'tag two',
                        'slug' => 'tag-two',
                    ],
                    [
                        'id' => 3,
                        'name' => 'tag three',
                        'slug' => 'tag-three',
                    ],
                ],
                'tagsCount' => 3,
            ],
            'tags removed' => [
                'payload' => ['tag one'],
                'tagsJson' => [
                    [
                        'id' => 1,
                        'name' => 'tag one',
                        'slug' => 'tag-one',
                    ],
                ],
                'tagsCount' => 1,
            ],
            'tags replaced' => [
                'payload' => ['tag three', 'tag four'],
                'tagsJson' => [
                    [
                        'id' => 3,
                        'name' => 'tag three',
                        'slug' => 'tag-three',
                    ],
                    [
                        'id' => 4,
                        'name' => 'tag four',
                        'slug' => 'tag-four',
                    ],
                ],
                'tagsCount' => 2,
            ],
        ];
    }

    private function validationProvider(): array
    {
        return [
            'title not string' => [
                'payload' => ['title' => ['::title::']],
                'field' => 'title',
                'error' => 'must be a string',
            ],
            'title longer than 255 chars' => [
                'payload' => ['title' => str_repeat('t', 256)],
                'field' => 'title',
                'error' => 'must not be greater than 255',
            ],
            'url not valid' => [
                'payload' => ['url' => '::url::'],
                'field' => 'url',
                'error' => 'must be a valid URL',
            ],
            'url longer than 255 chars' => [
                'payload' => ['url' => 'https://'.str_repeat('laravel', 256).'.com'],
                'field' => 'url',
                'error' => 'must not be greater than 255',
            ],
            'favorite not boolean' => [
                'payload' => ['favorite' => '::url::'],
                'field' => 'favorite',
                'error' => 'must be true or false',
            ],
            'tags not array' => [
                'payload' => ['tags' => '::tag::'],
                'field' => 'tags',
                'error' => 'must be an array',
            ],
            'tags not an array of string' => [
                'payload' => ['tags' => [['k']]],
                'field' => 'tags.0',
                'error' => 'must be a string',
            ],
            'tags exceed 255 chars' => [
                'payload' => ['tags' => [str_repeat('t', 256)]],
                'field' => 'tags.0',
                'error' => 'must not be greater than 255',
            ],
        ];
    }
}
