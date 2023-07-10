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

    /** @test */
    public function tags_can_be_searched_by_name()
    {
        Tag::factory()
            ->create(['name' => strtolower('Documentation')]);

        $portfolioTag = Tag::factory()
            ->create(['name' => strtolower('Portfolio')]);

        $projectExamplesTag = Tag::factory()
            ->create(['name' => strtolower('Project examples')]);

        $tags = Tag::query()
            ->search('p')
            ->get();

        $tags = $tags->collect()->pluck('name');

        $this->assertCount(2, $tags);
        $this->assertContains($projectExamplesTag->name, $tags);
        $this->assertContains($portfolioTag->name, $tags);
    }
}
