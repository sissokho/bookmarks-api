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
            ->favorite()
            ->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(route('api.v1.favorites.destroy', ['bookmark' => $bookmark]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $bookmark->id,
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
    public function validation_exception_is_thrown_if_bookmark_is_not_in_the_favorites(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->create(['favorite' => false]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(route('api.v1.favorites.destroy', ['bookmark' => $bookmark]));

        $response->assertUnprocessable()
            ->assertJson(['message' => 'This bookmark is not in the favorites.']);
    }

    /** @test */
    public function archived_bookmark_cant_be_removed_from_the_favorites(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->trashed()
            ->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(route('api.v1.favorites.destroy', ['bookmark' => $bookmark]));

        $response->assertUnprocessable()
            ->assertJson(['message' => 'Cannot perform this action on an archived bookmark.']);
    }
}
