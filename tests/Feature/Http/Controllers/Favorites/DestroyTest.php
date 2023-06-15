<?php

namespace Tests\Feature\Http\Controllers\Favorites;

use App\Models\Bookmark;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_must_be_authenticated(): void
    {
        $response = $this->deleteJson(route('api.v1.favorites.destroy', ['bookmark' => 1]));

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_cannot_remove_from_the_favorites_a_bookmark_that_does_not_exist(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->deleteJson(route('api.v1.favorites.destroy', ['bookmark' => 1]));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_remove_from_the_favorites_a_bookmark_that_does_not_belong_to_him(): void
    {
        $someonesBookmark = Bookmark::factory()->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->deleteJson(route('api.v1.favorites.destroy', ['bookmark' => $someonesBookmark]));

        $response->assertForbidden();
    }

    /** @test */
    public function bookmark_can_be_removed_from_the_favorites(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->has(Tag::factory()->state(['name' => strtolower('Tag One')]))
            ->create(['favorite' => true]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(route('api.v1.favorites.destroy', ['bookmark' => $bookmark]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => $bookmark->title,
                    'url' => $bookmark->url,
                    'favorite' => false,
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
            'favorite' => false,
        ]);
    }

    /** @test */
    public function update_query_is_not_executed_if_bookmark_has_already_been_removed_from_the_favorites(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->create(
                [
                    'favorite' => false,
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ]
            );

        Sanctum::actingAs($user);

        $this->deleteJson(route('api.v1.favorites.destroy', ['bookmark' => $bookmark]));

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'favorite' => false,
            'updated_at' => now()->subDay(),
        ]);
    }
}
