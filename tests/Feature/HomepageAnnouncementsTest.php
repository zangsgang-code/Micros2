<?php

use App\Models\Announcement;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('homepage renders full announcement content for both short and long announcements', function () {
    Announcement::create([
        'title' => 'Short announcement',
        'body' => '<p>A short update.</p>',
        'audience' => ['public'],
        'is_published' => true,
    ]);

    Announcement::create([
        'title' => 'Long announcement',
        'body' => '<p>'.str_repeat('A', 161).'</p>',
        'audience' => ['public'],
        'is_published' => true,
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Short announcement')
        ->assertSee('Long announcement')
        ->assertSee('class="ann-card__modal-content" hidden', false)
        ->assertSee('<p>A short update.</p>', false)
        ->assertSee('<p>'.str_repeat('A', 161).'</p>', false);

    expect(substr_count($response->getContent(), 'class="ann-card__modal-content" hidden'))->toBe(2);
});

test('homepage hero course displays category and level tags above the course title', function () {
    Course::create([
        'title' => 'Featured course title',
        'slug' => Str::uuid()->toString(),
        'category' => 'Information Technology',
        'level' => 'Intermediate',
        'approval_status' => 'approved',
        'is_approved' => true,
        'is_published' => true,
        'is_featured' => true,
    ]);

    $response = $this->get('/')->assertOk();
    preg_match('/<a class="hero__slide[^>]*>(.*?)<\/a>/s', $response->getContent(), $heroSlide);

    expect($heroSlide)->not->toBeEmpty()
        ->and(strpos($heroSlide[1], 'hero-course-tags'))->toBeLessThan(strpos($heroSlide[1], 'hero-course-title'))
        ->and($heroSlide[1])->toContain('<span>Information Technology</span>')
        ->and($heroSlide[1])->toContain('<span>Intermediate</span>');
});
