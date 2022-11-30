<?php

namespace Tests\Feature\Auth;

use App\Mail\ApiKeyGenerated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Tests\TestCase;

class ApiKeyRegenerationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function api_key_can_be_regenerated(): void
    {
        $user = User::factory()->create();

        $oldApiKey = $user->createToken('apiKey');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame($oldApiKey->accessToken->token, $user->tokens()->where('name', 'apiKey')->sole()->token);

        $response = $this->postJson(route('api.v1.regenerate'), ['email' => $user->email, 'password' => 'password']);

        $response
            ->assertOk()
            ->assertExactJson([
                'message' => 'A new API Key was generated and sent to your email address.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'apiKey',
        ]);
        $this->assertNotSame($oldApiKey->accessToken->token, $user->tokens()->where('name', 'apiKey')->sole()->token);
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

    /** @test */
    public function the_new_api_key_is_sent_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->postJson(route('api.v1.regenerate'), ['email' => $user->email, 'password' => 'password']);

        Mail::assertQueued(
            ApiKeyGenerated::class,
            function (ApiKeyGenerated $mail) use ($user): bool {
                $apiKey = $user->tokens()->where('name', 'apiKey')->sole();
                [$id, $token] = explode('|', $mail->apiKey, 2);

                return $mail->hasTo($user->email) && hash_equals($apiKey->token, hash('sha256', $token));
            }
        );
    }
}
