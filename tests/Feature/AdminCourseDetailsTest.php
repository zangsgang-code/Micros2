<?php

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin course details use the short description and avoid repeating level and duration in the hero', function () {
    $faculty = User::factory()->faculty()->create();
    $admin = User::factory()->admin()->create();
    $course = Course::create([
        'title' => 'Intermediate Information Technology',
        'slug' => 'intermediate-information-technology',
        'short_description' => 'Short field only.',
        'description' => '<p>Rich-text editor content.</p>',
        'category' => 'Information Technology',
        'level' => 'Intermediate',
        'duration' => '4h',
        'certificate_enabled' => true,
        'certificate_mode' => 'auto',
        'is_published' => true,
        'created_by' => $faculty->id,
    ]);
    CourseModule::create([
        'course_id' => $course->id,
        'title' => 'Module with details',
        'description' => '<h2>Module overview heading</h2><p>Configured module details.</p>',
        'order' => 1,
    ]);
    CourseModule::create([
        'course_id' => $course->id,
        'title' => 'Module without details',
        'order' => 2,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.courses.show', $course->id))
        ->assertOk()
        ->assertSee('<p class="hero-desc">Short field only.</p>', false)
        ->assertSee('Module overview heading')
        ->assertSee('Configured module details.')
        ->assertSee('No module description provided.')
        ->assertSee('Course review guide')
        ->assertSee('data-tour-step-target="admin-course-review-overview"', false)
        ->assertSee('data-tour-step-target="admin-course-review-status"', false)
        ->assertSee('data-tour-step-target="admin-course-review-approve"', false)
        ->assertSee('data-tour-step-target="admin-course-review-deny"', false)
        ->assertSee('data-tour-step-target="admin-course-review-delete"', false)
        ->assertSee('data-tour-step-target="admin-course-review-readiness"', false)
        ->assertSee('data-tour-step-target="admin-course-review-stats"', false)
        ->assertSee('data-tour-step-target="admin-course-review-description"', false)
        ->assertSee('data-tour-step-target="admin-course-review-syllabus"', false)
        ->assertSee('data-tour-step-target="admin-course-review-skills"', false)
        ->assertSee('data-tour-step-target="admin-course-review-content"', false)
        ->assertSee('data-tour-step-target="admin-course-review-award"', false)
        ->assertSee('data-tour-step-target="admin-course-review-progress"', false)
        ->assertSee('data-tour-step-target="admin-course-review-verification"', false)
        ->assertSee('data-tour-step-target="admin-course-review-fullscreen"', false)
        ->assertSee('data-tour-target="admin-course-review-fullscreen"', false)
        ->assertSee('data-tour-placement="viewport-bottom-right"', false)
        ->assertSee('id="admin-course-content"', false)
        ->assertSee('id="course-content-fullscreen"', false)
        ->assertSee('View course content full screen')
        ->assertSeeText('Intermediate')
        ->assertSeeText('4h');

    foreach ([
        'admin-course-review-overview',
        'admin-course-review-status',
        'admin-course-review-approve',
        'admin-course-review-deny',
        'admin-course-review-delete',
        'admin-course-review-readiness',
        'admin-course-review-stats',
        'admin-course-review-description',
        'admin-course-review-syllabus',
        'admin-course-review-skills',
        'admin-course-review-content',
        'admin-course-review-award',
        'admin-course-review-progress',
        'admin-course-review-verification',
        'admin-course-review-fullscreen',
    ] as $target) {
        $response->assertSee('data-tour-target="'.$target.'"', false);
    }

    preg_match('/<div class="meta-row">(.*?)<\/div>/s', $response->getContent(), $metaRow);

    expect($metaRow)->not->toBeEmpty()
        ->and($metaRow[1])->not->toContain('Intermediate')
        ->and($metaRow[1])->not->toContain('4h');

    preg_match('/<section class="card" id="completion-award"[^>]*>(.*?)<\/section>/s', $response->getContent(), $completionAward);

    expect($completionAward)->not->toBeEmpty()
        ->and($completionAward[1])->toContain('This certificate acknowledges that the above-named individual has demonstrated')
        ->and($completionAward[1])->toContain('proficiency in the competencies covered by this micro-credential.')
        ->and($completionAward[1])->not->toContain('Rich-text editor content.');
});

test('approved course review guide includes the unpublish control instead of pending review actions', function () {
    $faculty = User::factory()->faculty()->create();
    $admin = User::factory()->admin()->create();
    $course = Course::create([
        'title' => 'Approved course',
        'slug' => 'approved-course-review-guide',
        'approval_status' => 'approved',
        'is_approved' => true,
        'is_published' => true,
        'created_by' => $faculty->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.courses.show', $course->id))
        ->assertOk()
        ->assertSee('data-tour-step-target="admin-course-review-unpublish"', false)
        ->assertSee('data-tour-target="admin-course-review-unpublish"', false)
        ->assertDontSee('data-tour-step-target="admin-course-review-approve"', false)
        ->assertDontSee('data-tour-step-target="admin-course-review-deny"', false)
        ->assertDontSee('data-tour-step-target="admin-course-review-readiness"', false);
});
