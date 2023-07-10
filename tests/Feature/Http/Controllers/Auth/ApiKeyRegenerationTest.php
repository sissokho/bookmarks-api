<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Actions\GenerateApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ApiKeyRegenerationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function api_key_can_be_regenerated(): void
    {
        $spy = $this->spy(GenerateApiKey::class);

        $user = User::factory()->create();

        $response = $this->postJson(route('api.v1.regenerate'), ['email' => $user->email, 'password' => 'password']);

        $spy->shouldHaveReceived('__invoke')
            ->with(
                Mockery::on(fn (User $argument): bool => $argument->id === $user->id),
                true
            );

        $response
            ->assertOk()
            ->assertExactJson([
                'message' => 'A new API Key was generated and sent to your email address.',
            ]);
    }

    /** @test */
    public function email_and_password_are_required(): void
    {
        $response = $this->postJson(route('api.v1.regenerate'));

        $response->assertUnprocessable()
            ->assertInvalid([
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);
    }

    /** @test */
    public function email_must_be_valid(): void
    {
        $user = User::factory()
            ->state(['email' => 'john'])
            ->make();

        $response = $this->postJson(route('api.v1.regenerate'), $user->only('email', 'password'));

        $response->assertUnprocessable()
            ->assertInvalid([
                'email' => 'The email must be a valid email address.',
            ]);
    }

    /** @test */
    public function user_must_provide_valid_credentials(): void
    {
        $user = User::factory()
            ->state(['email' => 'john.doe@gmail.com'])
            ->create();

        // Correct email, wrong password
        $response = $this->postJson(route('api.v1.regenerate'), ['email' => $user->email, 'password' => 'wrong-one']);

        $response->assertUnprocessable()
            ->assertInvalid([
                'email' => 'The provided credentials are incorrect.',
            ]);

        // Wrong email, correct password
        $response = $this->postJson(route('api.v1.regenerate'), ['email' => 'john.12@gmail.com', 'password' => 'password']);

        $response->assertUnprocessable()
            ->assertInvalid([
                'email' => 'The provided credentials are incorrect.',
            ]);

        // Wrong email, Wrong password
        $response = $this->postJson(route('api.v1.regenerate'), ['email' => 'john.12@gmail.com', 'password' => 'wrong-one']);

        $response->assertUnprocessable()
            ->assertInvalid([
                'email' => 'The provided credentials are incorrect.',
            ]);
    }
}
