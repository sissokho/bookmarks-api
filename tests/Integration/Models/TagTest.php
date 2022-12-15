<?php

namespace Tests\Integration\Models;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function slug_is_generated_before_tag_is_created(): void
    {
        $tag = Tag::factory()
            ->state(['name' => 'Test Tag'])
            ->create();

        $this->assertSame('test-tag', $tag->slug);
    }

    /** @test */
    public function slug_is_regenerated_before_tag_is_updated(): void
    {
        $tag = Tag::factory()
            ->state(['name' => 'Test Tag'])
            ->create();

        $tag->update(['name' => 'Laravel Articles']);

        $this->assertSame('laravel-articles', $tag->slug);
    }
}
