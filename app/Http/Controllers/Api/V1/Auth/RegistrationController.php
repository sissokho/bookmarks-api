<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\GenerateApiKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Models\User;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RegistrationController extends Controller
{
    public function __invoke(RegisterRequest $request, GenerateApiKey $generateApiKey): JsonResponse
    {
        $user = User::create([
            ...$request->safe()->all(),
            'password' => Hash::make(
                strval($request->validated('password'))
            ),
        ]);

        $generateApiKey($user);

        return response()->json(
            ['message' => 'Your account was successfully created. Your api key was sent to your email address.'],
            Response::HTTP_CREATED
        );
    }
}
