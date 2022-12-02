<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\GenerateApiKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiKeyRegenerationRequest;
use App\Models\User;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ApiKeyRegenerationController extends Controller
{
    public function __invoke(ApiKeyRegenerationRequest $request, GenerateApiKey $generateApiKey): JsonResponse
    {
        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check(strval($request->validated('password')), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $generateApiKey($user, regenerate: true);

        return response()->json(
            ['message' => 'A new API Key was generated and sent to your email address.'],
            Response::HTTP_OK
        );
    }
}
