<?php

namespace Tests\Feature\Tags;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_update_one_of_his_tag(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $tag = Tag::factory()
            ->for($user)
            ->state(['name' => 'Test tag'])
            ->create();

        $response = $this->patchJson(route('api.v1.tags.update', ['tag' => $tag]), [
            'name' => 'PHP Tips',
        ]);

        $response->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $tag->id,
                    'name' => 'PHP Tips',
                    'slug' => 'php-tips',
                    'created_at' => $tag->created_at,
                ],
            ]);

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'PHP Tips',
            'slug' => 'php-tips',
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_one_of_his_tag(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()
            ->for($user)
            ->state(['name' => 'Test tag'])
            ->create();

        $response = $this->patchJson(route('api.v1.tags.update', ['tag' => $tag]), [
            'name' => 'PHP Tips',
        ]);

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /** @test */
    public function user_cannot_update_a_tag_that_does_not_belong_to_him(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()
            ->for($user)
            ->state(['name' => 'Test tag'])
            ->create();

        Sanctum::actingAs(User::factory()->make());

        $response = $this->patchJson(route('api.v1.tags.update', ['tag' => $tag]), [
            'name' => 'PHP Tips',
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    /**
     * @test
     * @dataProvider invalidNames
     */
    public function tag_name_is_validated($invalidName, $errorMsg): void
    {
        $user = User::factory()->create();

        [$tag1] = Tag::factory()
            ->count(2)
            ->for($user)
            ->state(new Sequence(
                ['name' => 'Test tag'],
                ['name' => 'Another tag']
            ))
            ->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.tags.update', ['tag' => $tag1]), [
            'name' => $invalidName,
        ]);

        $response->assertUnprocessable()
            ->assertInvalid([
                'name' => $errorMsg,
            ]);
    }

    /** @test */
    public function user_can_keep_the_same_tag_name(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()
            ->for($user)
            ->state(['name' => 'Test tag'])
            ->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('api.v1.tags.update', ['tag' => $tag]), [
            'name' => 'Test tag',
        ]);

        $response->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $tag->id,
                    'name' => 'Test tag',
                    'slug' => 'test-tag',
                    'created_at' => $tag->created_at,
                ],
            ]);

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Test tag',
            'slug' => 'test-tag',
        ]);
    }

    public function invalidNames(): array
    {
        return [
            'name is empty' => ['', 'The name field is required.'],
            'name is longer than 255 chars' => [Str::of('a')->repeat(256), 'The name must not be greater than 255 characters.'],
            'name is not unique for this user' => ['Another tag', 'The name has already been taken.'],
        ];
    }
}
