<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Actions\GenerateApiKey;
use App\Models\User;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Str;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_an_account(): void
    {
        $spy = $this->spy(GenerateApiKey::class);

        $user = User::factory()->make();

        $response = $this->postJson(route('api.v1.register'), $user->only('name', 'email', 'password'));

        $spy->shouldHaveReceived('__invoke')
            ->with(
                Mockery::on(fn (User $argument): bool => $argument->id === $user->where('email', $user->email)->value('id'))
            );

        $response
            ->assertCreated()
            ->assertExactJson([
                'message' => 'Your account was successfully created. Your api key was sent to your email address.',
            ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => $user->email,
        ]);
    }

    /** @test */
    public function fields_are_required(): void
    {
        $response = $this->postJson(route('api.v1.register'));

        $response->assertUnprocessable()
            ->assertInvalid([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);
    }

    /** @test */
    public function email_must_be_valid(): void
    {
        $user = User::factory()
            ->state(['email' => 'email'])
            ->make();

        $response = $this->postJson(route('api.v1.register'), $user->only('name', 'email', 'password'));

        $response->assertUnprocessable()
            ->assertInvalid([
                'email' => 'The email must be a valid email address.',
            ]);
    }

    /** @test */
    public function fields_must_not_exceed_255_characters(): void
    {
        $response = $this->postJson(route('api.v1.register'), [
            'name' => Str::of('john')->repeat(70),
            'email' => Str::of('johndoe')->repeat(80).'@gmail.com',
            'password' => 'john1234',
        ]);

        $response->assertUnprocessable()
            ->assertInvalid([
                'name' => 'The name must not be greater than 255 characters.',
                'email' => 'The email must not be greater than 255 characters.',
            ]);
    }

    /** @test */
    public function fields_cannot_be_lower_than_their_minimum_length(): void
    {
        $response = $this->postJson(route('api.v1.register'), [
            'name' => 'a',
            'email' => 'john.doe@gmail.com',
            'password' => 'john123',
        ]);

        $response->assertUnprocessable()
            ->assertInvalid([
                'name' => 'The name must be at least 2 characters.',
                'password' => 'The password must be at least 8 characters.',
            ]);
    }

    /** @test */
    public function password_is_hashed(): void
    {
        $spy = $this->spy(GenerateApiKey::class);

        $user = User::factory()
            ->state(['password' => 'john1234'])
            ->make();

        $this->postJson(route('api.v1.register'), $user->only('name', 'email', 'password'));

        $newUser = User::where('email', $user->email)->sole();

        $spy->shouldHaveReceived('__invoke')
            ->with(
                Mockery::on(fn (User $argument): bool => $argument->id === $newUser->id)
            );

        $this->assertNotSame($newUser->password, 'john1234');
        $this->assertTrue(Hash::check('john1234', $newUser->password));
    }
}
