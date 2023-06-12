<?php

namespace Tests\Feature\Models;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function slug_is_automatically_generated_before_saving_into_the_database()
    {
        $tag = Tag::factory()->create(['name' => 'PHP Tips']);

        $this->assertSame('php-tips', $tag->slug);
    }
}
