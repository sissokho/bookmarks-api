<?php

namespace Tests\Feature\Actions;

use App\Actions\FetchOrCreateTags;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FetchOrCreateTagsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tags_are_converted_into_lowercase_before_saving()
    {
        $tags = collect(['PHP Tips']);

        $tags = (new FetchOrCreateTags)($tags);

        $this->assertSame($tags[0]->name, 'php tips');
    }

    /** @test */
    public function extra_spaces_are_ignored()
    {
        $tags = collect(['PHP                  Tips']);

        $tags = (new FetchOrCreateTags)($tags);

        $this->assertSame($tags[0]->name, 'php tips');
    }

    /** @test */
    public function duplicates_are_ignored()
    {
        $tags = collect(['PHP Tips', 'PHP         Tips', 'php tips']);

        $tags = (new FetchOrCreateTags)($tags);

        $this->assertCount(1, $tags);
        $this->assertSame($tags[0]->name, 'php tips');
    }

    /** @test */
    public function existing_tags_are_fetched()
    {
        Tag::factory()
            ->count(2)
            ->state(new Sequence(
                ['name' => strtolower('Tag One')],
                ['name' => strtolower('Tag Two')]
            ))
            ->create();

        $tags = collect(['Tag One', 'Tag Two']);

        $tags = (new FetchOrCreateTags)($tags);

        $this->assertCount(2, $tags);
        $this->assertDatabaseCount('tags', 2); //We Still have 2 tags. No new tags were created.
        $this->assertSame($tags[0]->name, 'tag one');
        $this->assertSame($tags[1]->name, 'tag two');
    }

    /** @test */
    public function non_existing_tags_are_created()
    {
        Tag::factory()->create(['name' => strtolower('Tag One')]);

        $tags = collect(['Tag One', 'Tag Two', 'Tag Three']);

        $tags = (new FetchOrCreateTags)($tags);

        $this->assertCount(3, $tags);
        $this->assertDatabaseCount('tags', 3);
        $this->assertSame($tags[0]->name, 'tag one');
        $this->assertSame($tags[1]->name, 'tag two');
        $this->assertSame($tags[2]->name, 'tag three');
    }
}
