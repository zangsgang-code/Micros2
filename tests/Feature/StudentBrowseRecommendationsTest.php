<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPublishedBrowseCourse(string $title, array $attributes = []): Course
{
    return Course::create(array_merge([
        'title' => $title,
        'slug' => str($title)->slug().'-'.uniqid(),
        'is_published' => true,
    ], $attributes));
}

test('recommended filter shows no courses when the student has no profile skills', function () {
    $student = User::factory()->create();
    createPublishedBrowseCourse('General Course');

    $response = $this->actingAs($student)->get(route('courses.browse', ['recommended' => 1]));

    $response->assertOk()
        ->assertSee('No recommended courses yet.')
        ->assertSee('Browse guide')
        ->assertSee('data-tour-target="student-course-filters"', false)
        ->assertDontSee('General Course');
});

test('recommended filter only shows courses matching the student profile skills', function () {
    $student = User::factory()->create([
        'skills_have' => ['data analysis'],
        'skills_want' => [],
    ]);
    createPublishedBrowseCourse('Recommended Course', [
        'related_skills' => ['Data Analysis'],
    ]);
    createPublishedBrowseCourse('Other Course', [
        'related_skills' => ['Graphic Design'],
    ]);

    $response = $this->actingAs($student)->get(route('courses.browse', ['recommended' => 1]));

    $response->assertOk()
        ->assertSee('Recommended Course')
        ->assertSee('data-tour-target="student-first-course"', false)
        ->assertSee('Review a course and enroll')
        ->assertDontSee('Other Course');
});

test('catalog guide auto-opens only when requested by the first-login flow', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('courses.browse', ['guide' => 1]))
        ->assertOk()
        ->assertSee('if (true)', false)
        ->assertSee("searchParams.delete('guide')", false);
});

test('student course details explain enrollment in the course guide', function () {
    $student = User::factory()->create();
    $course = createPublishedBrowseCourse('Enrollment guide course', [
        'is_approved' => true,
        'approval_status' => 'approved',
    ]);

    $this->actingAs($student)
        ->get(route('courses.show', $course->id))
        ->assertOk()
        ->assertSee('Course guide')
        ->assertSee('Enroll or continue learning')
        ->assertSee('data-tour-placement="viewport-bottom-right"', false)
        ->assertSee('data-tour-target="student-enrollment-action"', false)
        ->assertSee('Enroll now');
});
