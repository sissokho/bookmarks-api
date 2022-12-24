<?php

namespace Tests\Feature\Tags;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_delete_one_of_his_tags(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(route('api.v1.tags.destroy', ['tag' => $tag]));

        $response->assertNoContent();

        $this->assertDatabaseEmpty('tags');
    }

    /** @test */
    public function only_an_authenticated_user_can_delete_a_tag(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()
            ->for($user)
            ->create();

        $response = $this->deleteJson(route('api.v1.tags.destroy', ['tag' => $tag]));

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /** @test */
    public function user_cannot_delete_a_tag_that_does_not_belong_to_him()
    {
        $user = User::factory()->create();

        $tag = Tag::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->deleteJson(route('api.v1.tags.destroy', ['tag' => $tag]));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }
}
