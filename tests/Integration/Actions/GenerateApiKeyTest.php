<?php

namespace Tests\Integration\Actions;

use App\Actions\GenerateApiKey;
use App\Mail\ApiKeyGenerated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Tests\TestCase;

class GenerateApiKeyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function api_key_is_generated_for_the_first_time_and_sent_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $apiKey = app(GenerateApiKey::class)($user, regenerate: false);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'apiKey',
            'token' => $apiKey->accessToken->token,
        ]);

        Mail::assertQueued(
            ApiKeyGenerated::class,
            fn (ApiKeyGenerated $mail): bool => $mail->hasTo($user->email) && $mail->apiKey === $apiKey->plainTextToken
        );
    }

    /** @test */
    public function api_key_can_be_regenerated_and_sent_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $oldApiKey = $user->createToken('apiKey');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame($oldApiKey->accessToken->token, $user->tokens()->where('name', 'apiKey')->sole()->token);

        $newApiKey = app(GenerateApiKey::class)($user, regenerate: true);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'apiKey',
            'token' => $newApiKey->accessToken->token,
        ]);

        Mail::assertQueued(
            ApiKeyGenerated::class,
            fn (ApiKeyGenerated $mail): bool => $mail->hasTo($user->email) && $mail->apiKey === $newApiKey->plainTextToken
        );
    }
}
