<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\FetchOrCreateTags;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_must_be_authenticated(): void
    {
        $response = $this->postJson(route('api.v1.bookmarks.store'));

        $response->assertUnauthorized();
    }

    /**
     * @test
     *
     * @dataProvider bookmarkWithoutTagsProvider
     */
    public function user_can_create_a_bookmark_without_tags(array $payload): void
    {
        $this->freezeTime();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.bookmarks.store'), $payload);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'id' => 1,
                    'title' => $payload['title'],
                    'url' => $payload['url'],
                    'favorite' => $payload['favorite'],
                    'archived' => false,
                    'created_at' => now()->toDateTimeString(),
                ],
            ]);

        $this->assertDatabaseCount('bookmarks', 1);
        $this->assertDatabaseHas('bookmarks', [
            'id' => 1,
            'title' => $payload['title'],
            'url' => $payload['url'],
            'favorite' => $payload['favorite'],
            'deleted_at' => null,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function user_can_create_a_bookmark_with_tags(): void
    {
        $this->freezeTime();

        Sanctum::actingAs(User::factory()->create());

        [$phpTag, $laravelTag] = Tag::factory()
            ->count(2)
            ->state(new Sequence(
                ['name' => 'php tips'],
                ['name' => 'laravel tips']
            ))
            ->create();

        $payload = [
            'title' => '::title::',
            'url' => 'https://laravel.com',
            'favorite' => false,
            'tags' => ['PHP Tips', 'Laravel Tips'],
        ];

        $response = $this->postJson(route('api.v1.bookmarks.store'), $payload);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'id' => 1,
                    'title' => $payload['title'],
                    'url' => $payload['url'],
                    'favorite' => $payload['favorite'],
                    'archived' => false,
                    'created_at' => now()->toDateTimeString(),
                    'tags' => [
                        [
                            'id' => $phpTag->id,
                            'name' => $phpTag->name,
                            'slug' => $phpTag->slug,
                            'created_at' => $phpTag->created_at->toDateTimeString(),
                        ],
                        [
                            'id' => $laravelTag->id,
                            'name' => $laravelTag->name,
                            'slug' => $laravelTag->slug,
                            'created_at' => $laravelTag->created_at->toDateTimeString(),
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseCount('bookmark_tag', 2);

        $this->assertDatabaseHas('bookmark_tag', [
            'bookmark_id' => 1,
            'tag_id' => 1,
        ]);

        $this->assertDatabaseHas('bookmark_tag', [
            'bookmark_id' => 1,
            'tag_id' => 2,
        ]);
    }

    /** @test */
    public function createorfetchaction_is_used_by_the_controller(): void
    {
        $action = $this->spy(FetchOrCreateTags::class);

        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'title' => '::title::',
            'url' => 'https://laravel.com',
            'favorite' => false,
            'tags' => ['Tag One', 'Tag Two'],
        ];

        $this->postJson(route('api.v1.bookmarks.store'), $payload);

        $action->shouldHaveReceived('__invoke')->once();
    }

    /**
     * @test
     *
     * @dataProvider validationProvider
     */
    public function inputs_are_validated(array $payload, string $field, string $error): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.v1.bookmarks.store'), $payload);

        $response->assertInvalid([$field => $error]);
    }

    private function validationProvider(): array
    {
        $defaultPayload = [
            'title' => '::title::',
            'url' => 'https://laravel.com',
            'favorite' => false,
        ];

        return [
            'missing title' => [
                'payload' => Arr::except($defaultPayload, 'title'),
                'field' => 'title',
                'error' => 'is required',
            ],
            'title not string' => [
                'payload' => [...$defaultPayload, 'title' => ['::title::']],
                'field' => 'title',
                'error' => 'must be a string',
            ],
            'title longer than 255 chars' => [
                'payload' => [...$defaultPayload, 'title' => str_repeat('t', 256)],
                'field' => 'title',
                'error' => 'must not be greater than 255',
            ],
            'missing url' => [
                'payload' => Arr::except($defaultPayload, 'url'),
                'field' => 'url',
                'error' => 'is required',
            ],
            'url not valid' => [
                'payload' => [...$defaultPayload, 'url' => '::url::'],
                'field' => 'url',
                'error' => 'must be a valid URL',
            ],
            'url longer than 255 chars' => [
                'payload' => [...$defaultPayload, 'url' => 'https://' . str_repeat('laravel', 256) . '.com'],
                'field' => 'url',
                'error' => 'must not be greater than 255',
            ],
            'missing favorite' => [
                'payload' => Arr::except($defaultPayload, 'favorite'),
                'field' => 'favorite',
                'error' => 'is required',
            ],
            'favorite not boolean' => [
                'payload' => [...$defaultPayload, 'favorite' => '::url::'],
                'field' => 'favorite',
                'error' => 'must be true or false',
            ],
            'tags is not an array' => [
                'payload' => [...$defaultPayload, 'tags' => '::tag::'],
                'field' => 'tags',
                'error' => 'must be an array',
            ],
            'tags is an empty array' => [
                'payload' => [...$defaultPayload, 'tags' => []],
                'field' => 'tags',
                'error' => 'must have at least 1 items',
            ],
            'tags not an array of string' => [
                'payload' => [...$defaultPayload, 'tags' => [['k']]],
                'field' => 'tags.0',
                'error' => 'must be a string',
            ],
            'tags exceed 255 chars' => [
                'payload' => [...$defaultPayload, 'tags' => [str_repeat('t', 256)]],
                'field' => 'tags.0',
                'error' => 'must not be greater than 255',
            ],
        ];
    }

    private function bookmarkWithoutTagsProvider(): array
    {
        $defaultPayload = [
            'title' => '::title::',
            'url' => 'https://laravel.com',
            'favorite' => false,
        ];

        return [
            'missing tags' => [$defaultPayload],
            'tags is null' => [[...$defaultPayload, 'tags' => null]],
            'tags is empty string' => [[...$defaultPayload, 'tags' => '']],
        ];
    }
}
