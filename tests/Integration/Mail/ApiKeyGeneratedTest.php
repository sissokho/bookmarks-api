<?php

namespace Tests\Integration\Mail;

use App\Mail\ApiKeyGenerated;
use Tests\TestCase;

class ApiKeyGeneratedTest extends TestCase
{
    /** @test */
    public function mailable_content(): void
    {
        $apiKey = 'jHPyd855ere887';

        $mailable = new ApiKeyGenerated('John Doe', $apiKey);

        $mailable->assertHasSubject('Bookmarks API: Api key')
            ->assertSeeInOrderInText([
                'Hello John Doe!',
                "Your new Api Key is {$apiKey}.",
                'Please use this key to authenticate to our API and perform requests.',
                'Thanks,',
                'The Bookmarks API Team',
            ]);
    }
}
