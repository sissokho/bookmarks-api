<?php

namespace Tests\Feature\Http\Controllers\Bookmarks;

use App\Models\Bookmark;
use App\Models\Tag;
use App\Models\User;
use Carbon\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_must_be_authenticated(): void
    {
        $response = $this->deleteJson(route('api.v1.bookmarks.destroy', ['bookmark' => 1]));

        $response->assertUnauthorized();
    }

    /** @test */
    public function cannot_delete_a_bookmark_that_does_not_exist(): void
    {
        Sanctum::actingAs(User::factory()->make());

        $response = $this->deleteJson(route('api.v1.bookmarks.destroy', ['bookmark' => 1]));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_delete_a_bookmark_that_does_not_belong_to_him(): void
    {
        $someonesBookmark = Bookmark::factory()->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->deleteJson(route('api.v1.bookmarks.destroy', ['bookmark' => $someonesBookmark]));

        $response->assertForbidden();
    }

    /** @test */
    public function bookmark_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $bookmark = Bookmark::factory()
            ->for($user)
            ->has(Tag::factory())
            ->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(route('api.v1.bookmarks.destroy', ['bookmark' => $bookmark]));

        $response->assertNoContent();

        $this->assertModelMissing($bookmark);
        $this->assertDatabaseCount('bookmark_tag', 0);
    }
}
