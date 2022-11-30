<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiKeyRegenerationRequest;
use App\Mail\ApiKeyGenerated;
use App\Models\User;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Mail;

class ApiKeyRegenerationController extends Controller
{
    public function __invoke(ApiKeyRegenerationRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check(strval($request->validated('password')), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $user->tokens()->where('name', 'apiKey')->delete();

        $apiKey = $user->createToken('apiKey');

        Mail::to($user)->send(new ApiKeyGenerated($user->name, $apiKey->plainTextToken));

        return response()->json(
            ['message' => 'A new API Key was generated and sent to your email address.'],
            Response::HTTP_OK
        );
    }
}
