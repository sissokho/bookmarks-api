<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\ApiKeyGenerated;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;
use Mail;

class GenerateApiKey
{
    public function __invoke(User $user, bool $regenerate = false): NewAccessToken
    {
        if ($regenerate) {
            $user->tokens()->where('name', 'apiKey')->delete();
        }

        $apiKey = $user->createToken('apiKey');

        Mail::to($user)->send(new ApiKeyGenerated($user->name, $apiKey->plainTextToken));

        return $apiKey;
    }
}
