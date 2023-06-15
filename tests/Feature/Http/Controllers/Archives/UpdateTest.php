<?php

namespace Tests\Feature\Http\Controllers\Archives;

use App\Models\Bookmark;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_must_be_authenticated(): void
    {
        $response = $this->patchJson(route('api.v1.archives.update', ['bookmark' => 1]));

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_cannot_add_a_bookmark_that_does_not_exist_to_the_archives(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->patchJson(route('api.v1.archives.update', ['bookmark' => 1]));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_add_a_bookmark_that_does_not_belong_to_him_to_the_archives(): void
    {
        $someonesBookmark = Bookmark::factory()->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->patchJson(route('api.v1.archives.update', ['bookmark' => $someonesBookmark]));

        $response->assertForbidden();
    }

    /** @test */
    public function bookmark_can_be_added_to_the_archives(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->has(Tag::factory()->state(['name' => strtolower('Tag One')]))
            ->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.archives.update', ['bookmark' => $bookmark]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => $bookmark->title,
                    'url' => $bookmark->url,
                    'favorite' => false,
                    'archived' => true,
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
            'deleted_at' => now(),
        ]);
    }

    /** @test */
    public function query_is_not_executed_if_bookmark_has_already_been_added_to_the_archives(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->create(
                [
                    'created_at' => now()->subWeek(),
                    'deleted_at' => now()->subDay(),
                ]
            );

        Sanctum::actingAs($user);

        $this->patchJson(route('api.v1.archives.update', ['bookmark' => $bookmark]));

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'deleted_at' => now()->subDay(),
        ]);
    }
}
