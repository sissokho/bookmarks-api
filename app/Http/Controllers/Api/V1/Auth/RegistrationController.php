<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\ApiKeyGenerated;
use App\Models\User;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Mail;

class RegistrationController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::create(
            $request->safe()->merge([
                'password' => Hash::make(
                    strval($request->safe()['password'])
                ),
            ])->all()
        );

        $apiKey = $user->createToken('apiKey');

        Mail::to($user)->send(new ApiKeyGenerated($user->name, $apiKey->plainTextToken));

        return response()->json(
            ['message' => 'Your account was successfully created. Your api key was sent to your email address.'],
            Response::HTTP_CREATED
        );
    }
}
