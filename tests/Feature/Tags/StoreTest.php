<?php

namespace Tests\Feature\Tags;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_a_tag(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Continuous Integration',
        ];

        $response = $this->postJson(route('api.v1.tags.store'), $payload);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => $payload['name'],
                    'slug' => 'continuous-integration',
                ],
            ]);

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseHas('tags', [
            'name' => $payload['name'],
            'slug' => 'continuous-integration',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_a_tag(): void
    {
        $response = $this->postJson(route('api.v1.tags.store'), [
            'name' => fake()->word(),
        ]);

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @test
     * @dataProvider invalidNames
     */
    public function tag_name_is_validated($invalidName, $errorMsg): void
    {
        $user = User::factory()->create();

        $user->tags()->create(['name' => 'Test tag']);

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.tags.store'), [
            'name' => $invalidName,
        ]);

        $response->assertUnprocessable()
            ->assertInvalid([
                'name' => $errorMsg,
            ]);
    }

    /** @test */
    public function different_users_can_create_tags_with_the_same_name(): void
    {
        Tag::factory()
            ->state(['name' => 'Test tag'])
            ->create();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.tags.store'), [
            'name' => 'Test tag',
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('tags', 2);
        $this->assertCount(2, Tag::where('name', 'Test tag')->get());
        $this->assertCount(1, Tag::where('user_id', $user->id)->get());
    }

    public function invalidNames(): array
    {
        return [
            'name is empty' => ['', 'The name field is required.'],
            'name is longer than 255 chars' => [Str::of('a')->repeat(256), 'The name must not be greater than 255 characters.'],
            'name is not unique for this user' => ['Test tag', 'The name has already been taken.'],
        ];
    }
}
