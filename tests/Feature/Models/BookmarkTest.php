<?php

namespace Tests\Feature\Models;

use App\Models\Bookmark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function bookmarks_can_be_searched_by_title_and_url()
    {
        Bookmark::factory()
            ->create(['title' => 'Laravel Website', 'url' => 'https://laravel.com']); // No matches

        $downingTech = Bookmark::factory()
            ->create(['title' => 'Luke Downing Blog', 'url' => 'downing.tech']); // Title and url match

        $dummyWebsite = Bookmark::factory()
            ->create(['title' => 'Some Dummy Website', 'url' => 'websiteisdown.com']); // url matches

        $bookmarks = Bookmark::query()
            ->search('down')
            ->get();

        $titles = $bookmarks->collect()->pluck('title');
        $urls = $bookmarks->collect()->pluck('url');

        $this->assertCount(2, $bookmarks);
        $this->assertContains($downingTech->title, $titles);
        $this->assertContains($downingTech->url, $urls);
        $this->assertContains($dummyWebsite->title, $titles);
        $this->assertContains($dummyWebsite->url, $urls);
    }
}
