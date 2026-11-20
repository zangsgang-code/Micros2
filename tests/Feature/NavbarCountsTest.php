<?php

use App\Models\Complaint;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LessonActivity;
use App\Models\LessonActivitySubmission;
use App\Models\Notification;
use App\Models\User;
use App\Services\MicrocredentialCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('authenticated topbar counts include only the signed-in users unread notifications and inbox threads', function () {
    $student = User::factory()->create();
    $otherStudent = User::factory()->create();

    Notification::create([
        'user_id' => $student->id,
        'title' => 'New course',
        'message' => 'A course is available.',
        'type' => 'course',
        'is_read' => false,
    ]);
    Notification::create([
        'user_id' => $student->id,
        'title' => 'Read course',
        'message' => 'Already read.',
        'type' => 'course',
        'is_read' => true,
    ]);
    Notification::create([
        'user_id' => $otherStudent->id,
        'title' => 'Other notification',
        'message' => 'Not yours.',
        'type' => 'course',
        'is_read' => false,
    ]);

    Complaint::create([
        'user_id' => $student->id,
        'source' => 'student',
        'subject' => 'Unread thread',
        'message' => 'Please help.',
        'status' => 'open',
        'last_reply_at' => now(),
        'student_read_at' => now()->subMinute(),
    ]);
    Complaint::create([
        'user_id' => $student->id,
        'source' => 'student',
        'subject' => 'Read thread',
        'message' => 'Resolved.',
        'status' => 'open',
        'last_reply_at' => now()->subMinute(),
        'student_read_at' => now(),
    ]);
    Complaint::create([
        'user_id' => $otherStudent->id,
        'source' => 'student',
        'subject' => 'Other thread',
        'message' => 'Not yours.',
        'status' => 'open',
        'last_reply_at' => now(),
        'student_read_at' => now()->subMinute(),
    ]);

    $this->actingAs($student)
        ->getJson(route('notifications.counts'))
        ->assertOk()
        ->assertExactJson([
            'unread_notifications' => 1,
            'unread_inbox' => 1,
            'sidebar_alerts' => [
                'admin_course_reviews' => 0,
                'admin_completion_reviews' => 0,
                'faculty_learner_progress' => false,
                'faculty_my_courses' => false,
            ],
        ]);
});

test('admin topbar inbox count includes unread threads and notification count', function () {
    $admin = User::factory()->admin()->create();
    Course::create([
        'title' => 'Course pending review',
        'slug' => Str::uuid()->toString(),
        'approval_status' => 'pending',
        'is_approved' => false,
        'is_published' => false,
    ]);

    Notification::create([
        'user_id' => $admin->id,
        'title' => 'New review',
        'message' => 'Review required.',
        'type' => 'course',
        'is_read' => false,
    ]);
    Complaint::create([
        'source' => 'visitor',
        'guest_name' => 'Website visitor',
        'subject' => 'Unread visitor thread',
        'message' => 'Please help.',
        'status' => 'open',
        'last_reply_at' => now(),
        'admin_read_at' => now()->subMinute(),
    ]);
    Complaint::create([
        'source' => 'visitor',
        'guest_name' => 'Website visitor',
        'subject' => 'Read visitor thread',
        'message' => 'Already reviewed.',
        'status' => 'open',
        'last_reply_at' => now()->subMinute(),
        'admin_read_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson(route('notifications.counts'))
        ->assertOk()
        ->assertExactJson([
            'unread_notifications' => 1,
            'unread_inbox' => 1,
            'sidebar_alerts' => [
                'admin_course_reviews' => 1,
                'admin_completion_reviews' => 0,
                'faculty_learner_progress' => false,
                'faculty_my_courses' => false,
            ],
        ]);

    $this->actingAs($admin)
        ->get(route('admin.complaints'))
        ->assertOk()
        ->assertSee('data-counts-url="'.route('notifications.counts').'"', false)
        ->assertSee('data-topbar-count-target="notifications"', false)
        ->assertSee('data-topbar-count-target="inbox"', false)
        ->assertSee('data-sidebar-alert-target="admin_course_reviews"', false)
        ->assertSee('data-sidebar-alert-dot', false);
});

test('faculty sidebar attention is available to the live count poll', function () {
    $faculty = User::factory()->faculty()->create();
    $student = User::factory()->create();
    $course = Course::create([
        'title' => 'Assignment review course',
        'slug' => Str::uuid()->toString(),
        'created_by' => $faculty->id,
        'approval_status' => 'draft',
        'is_published' => false,
    ]);
    $module = CourseModule::create([
        'course_id' => $course->id,
        'title' => 'Module one',
    ]);
    $lesson = CourseLesson::create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'title' => 'Lesson one',
    ]);
    $activity = LessonActivity::create([
        'lesson_id' => $lesson->id,
        'title' => 'Active assignment',
        'activity_type' => 'assignment',
        'is_active' => true,
    ]);
    LessonActivitySubmission::create([
        'lesson_activity_id' => $activity->id,
        'user_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($faculty)
        ->getJson(route('notifications.counts'))
        ->assertOk()
        ->assertJsonPath('sidebar_alerts.faculty_my_courses', true);

    $this->get(route('faculty.courses'))
        ->assertOk()
        ->assertSee('data-sidebar-alert-target="faculty_my_courses"', false)
        ->assertSee('data-sidebar-alert-dot', false);
});

test('faculty learner progress alert updates through the live count poll', function () {
    $faculty = User::factory()->faculty()->create();
    $course = Course::create([
        'title' => 'Verification course',
        'slug' => Str::uuid()->toString(),
        'created_by' => $faculty->id,
        'requires_faculty_verification' => true,
        'approval_status' => 'draft',
        'is_published' => false,
    ]);

    $this->actingAs($faculty)
        ->getJson(route('notifications.counts'))
        ->assertOk()
        ->assertJsonPath('sidebar_alerts.faculty_learner_progress', false);

    Enrollment::create([
        'user_id' => User::factory()->create()->id,
        'course_id' => $course->id,
        'completion_status' => MicrocredentialCompletionService::STATUS_AWAITING_FACULTY_VERIFICATION,
        'faculty_verification_status' => 'pending',
    ]);

    $this->getJson(route('notifications.counts'))
        ->assertOk()
        ->assertJsonPath('sidebar_alerts.faculty_learner_progress', true);

    $this->get(route('faculty.dashboard'))
        ->assertOk()
        ->assertSee('Learner progress needs attention', false);
});

test('topbar counts require authentication', function () {
    $this->getJson(route('notifications.counts'))
        ->assertRedirect(route('login'));
});
