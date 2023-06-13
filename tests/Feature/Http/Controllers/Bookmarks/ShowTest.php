<?php

namespace Tests\Feature\Http\Controllers\Bookmarks;

use App\Models\Bookmark;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_must_be_authenticated(): void
    {
        $response = $this->getJson(route('api.v1.bookmarks.show', ['bookmark' => 1]));

        $response->assertUnauthorized();
    }

    /** @test */
    public function cannot_see_the_details_of_a_bookmark_that_does_not_exist(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->getJson(route('api.v1.bookmarks.show', ['bookmark' => 1]));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_see_the_details_of_a_bookmark_that_does_not_belong_to_him(): void
    {
        $someonesBookmark = Bookmark::factory()->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->getJson(route('api.v1.bookmarks.show', ['bookmark' => $someonesBookmark]));

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_see_the_details_of_his_bookmarks(): void
    {
        $user = User::factory()->create();

        [$tagOne, $tagTwo] = Tag::factory()
            ->count(2)
            ->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->create();

        $bookmark->tags()->attach([$tagOne->id, $tagTwo->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.v1.bookmarks.show', ['bookmark' => $bookmark]));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => $bookmark->title,
                    'url' => $bookmark->url,
                    'favorite' => $bookmark->favorite,
                    'archived' => false,
                    'created_at' => $bookmark->created_at->toDateTimeString(),
                    'tags' => [
                        [
                            'id' => $tagOne->id,
                            'name' => $tagOne->name,
                            'slug' => $tagOne->slug,
                            'created_at' => $tagOne->created_at->toDateTimeString(),
                        ],
                        [
                            'id' => $tagTwo->id,
                            'name' => $tagTwo->name,
                            'slug' => $tagTwo->slug,
                            'created_at' => $tagTwo->created_at->toDateTimeString(),
                        ],
                    ],
                ],
            ]);
    }
}
