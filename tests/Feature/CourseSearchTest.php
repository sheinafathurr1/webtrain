<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CourseSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_courses_by_title_or_description(): void
    {
        $track = Track::factory()->create(['is_published' => true]);
        Course::factory()->create(['track_id' => $track->id, 'is_published' => true, 'title' => 'Belajar HTML Dasar']);
        Course::factory()->create(['track_id' => $track->id, 'is_published' => true, 'title' => 'Belajar PHP Lanjutan']);

        $component = Volt::test('pages.courses.index')->set('search', 'HTML');

        $courses = $component->get('tracks')->flatMap->courses;

        $this->assertCount(1, $courses);
        $this->assertSame('Belajar HTML Dasar', $courses->first()->title);
    }

    public function test_filter_by_track_only_shows_that_tracks_courses(): void
    {
        $trackA = Track::factory()->create(['is_published' => true]);
        $trackB = Track::factory()->create(['is_published' => true]);
        Course::factory()->create(['track_id' => $trackA->id, 'is_published' => true]);
        Course::factory()->create(['track_id' => $trackB->id, 'is_published' => true]);

        $component = Volt::test('pages.courses.index')->set('trackId', $trackA->id);

        $tracks = $component->get('tracks');

        $this->assertCount(1, $tracks);
        $this->assertSame($trackA->id, $tracks->first()->id);
    }

    public function test_no_match_shows_empty_result(): void
    {
        $track = Track::factory()->create(['is_published' => true]);
        Course::factory()->create(['track_id' => $track->id, 'is_published' => true, 'title' => 'Belajar HTML Dasar']);

        $component = Volt::test('pages.courses.index')->set('search', 'Nonexistent Topic Xyz');

        $this->assertTrue($component->get('tracks')->isEmpty());
    }

    public function test_reset_filters_clears_search_and_track(): void
    {
        $track = Track::factory()->create(['is_published' => true]);
        Course::factory()->create(['track_id' => $track->id, 'is_published' => true]);

        $component = Volt::test('pages.courses.index')
            ->set('search', 'something')
            ->set('trackId', $track->id)
            ->call('resetFilters');

        $component->assertSet('search', '')->assertSet('trackId', null);
    }
}
