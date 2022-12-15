<?php

namespace Tests\Feature\Tags;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_see_the_details_of_one_of_his_tags(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $tag = Tag::factory()
            ->for($user)
            ->create();

        $response = $this->getJson(route('api.v1.tags.show', ['tag' => $tag]));

        $response->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'created_at' => $tag->created_at,
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_the_details_of_one_of_his_tags(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()
            ->for($user)
            ->create();

        $response = $this->getJson(route('api.v1.tags.show', ['tag' => $tag]));

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /** @test */
    public function user_cannot_see_a_tag_that_does_not_belong_to_him(): void
    {
        $tag = Tag::factory()->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->getJson(route('api.v1.tags.show', ['tag' => $tag]));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }
}
