<?php

namespace Tests\Feature\Http\Controllers\Favorites;

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
        $response = $this->patchJson(route('api.v1.favorites.update', ['bookmark' => 1]));

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_cannot_add_a_bookmark_that_does_not_exist_to_the_favorites(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->patchJson(route('api.v1.favorites.update', ['bookmark' => 1]));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_add_a_bookmark_that_does_not_belong_to_him_to_the_favorites(): void
    {
        $someonesBookmark = Bookmark::factory()->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->patchJson(route('api.v1.favorites.update', ['bookmark' => $someonesBookmark]));

        $response->assertForbidden();
    }

    /** @test */
    public function bookmark_can_be_added_to_the_favorites(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->has(Tag::factory()->state(['name' => strtolower('Tag One')]))
            ->create(['favorite' => false]);

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.favorites.update', ['bookmark' => $bookmark]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => $bookmark->title,
                    'url' => $bookmark->url,
                    'favorite' => true,
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
            'favorite' => true,
        ]);
    }

    /** @test */
    public function validation_exception_is_thrown_if_bookmark_has_already_been_added_to_the_favorites(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->create(['favorite' => true]);

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.favorites.update', ['bookmark' => $bookmark]));

        $response->assertInvalid(['bookmark' => 'This bookmark has already been added to your favorites.']);
    }
}
