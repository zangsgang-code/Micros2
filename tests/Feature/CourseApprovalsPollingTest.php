<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('course approvals page exposes polling metadata and course ids', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::create([
        'title' => 'Course awaiting review',
        'slug' => Str::uuid()->toString(),
        'approval_status' => 'pending',
        'is_approved' => false,
        'is_published' => false,
    ]);
    $draft = Course::create([
        'title' => 'Private faculty draft',
        'slug' => Str::uuid()->toString(),
        'approval_status' => 'draft',
        'is_approved' => false,
        'is_published' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.courses'))
        ->assertOk()
        ->assertSee('data-poll-url="'.route('admin.courses.updates', [], false).'"', false)
        ->assertSee('data-course-id="'.$course->id.'"', false)
        ->assertDontSee('data-course-id="'.$draft->id.'"', false)
        ->assertSee('Course awaiting review');
});

test('course approvals polling returns courses submitted from existing drafts', function () {
    $admin = User::factory()->admin()->create();
    $faculty = User::factory()->faculty()->create();
    $course = Course::create([
        'title' => 'Newly submitted course',
        'slug' => Str::uuid()->toString(),
        'created_by' => $faculty->id,
        'approval_status' => 'draft',
        'is_published' => false,
    ]);
    $sinceAt = now()->subMinute();
    $course->update(['approval_status' => 'pending']);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.courses.updates', ['sinceAt' => $sinceAt->toIso8601String()]))
        ->assertOk()
        ->assertJsonStructure(['courses_html', 'draft_ids', 'checked_at', 'course_count'])
        ->assertJsonPath('course_count', 1);

    expect($response->json('courses_html'))
        ->toContain('data-course-id="'.$course->id.'"')
        ->toContain('Newly submitted course')
        ->toContain('Review course')
        ->and($response->json('draft_ids'))->toBe([]);
});

test('course approvals polling reports courses that returned to draft for removal', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::create([
        'title' => 'Returned course',
        'slug' => Str::uuid()->toString(),
        'approval_status' => 'pending',
        'is_approved' => false,
        'is_published' => false,
    ]);
    $sinceAt = now()->subMinute();
    $course->update(['approval_status' => 'draft']);

    $this->actingAs($admin)
        ->getJson(route('admin.courses.updates', ['sinceAt' => $sinceAt->toIso8601String()]))
        ->assertOk()
        ->assertJsonPath('draft_ids', [$course->id])
        ->assertJsonPath('courses_html', '')
        ->assertJsonPath('course_count', 1);
});

test('course approvals polling refreshes cards when a course changes to another visible status', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::create([
        'title' => 'Reviewed course',
        'slug' => Str::uuid()->toString(),
        'approval_status' => 'pending',
        'is_approved' => false,
        'is_published' => false,
    ]);
    $sinceAt = now()->subMinute();
    $course->update([
        'approval_status' => 'approved',
        'is_approved' => true,
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.courses.updates', ['sinceAt' => $sinceAt->toIso8601String()]))
        ->assertOk()
        ->assertJsonPath('draft_ids', [])
        ->assertJsonPath('course_count', 1);

    expect($response->json('courses_html'))
        ->toContain('data-course-id="'.$course->id.'"')
        ->toContain('data-status="approved"');
});

test('course approvals polling returns no cards when there are no recent submissions', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson(route('admin.courses.updates', ['sinceAt' => now()->toIso8601String()]))
        ->assertOk()
        ->assertJsonPath('draft_ids', [])
        ->assertJsonPath('courses_html', '')
        ->assertJsonPath('course_count', 0);
});
