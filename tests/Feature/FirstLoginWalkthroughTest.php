<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('student guided tour highlights dashboard areas and can be dismissed', function () {
    $student = User::factory()->create([
        'profile_completed' => true,
        'role_id' => User::ROLE_STUDENT,
    ]);

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Student guided tour')
        ->assertSee('Step <span id="first-login-tour-current">1</span> of 4', false)
        ->assertSee('role="dialog" aria-modal="true"', false)
        ->assertSee('Browse courses')
        ->assertSee('data-tour-target="student-browse-courses"', false)
        ->assertSee('data-tour-target="student-course-progress"', false)
        ->assertSee('first-login-tour-next');

    $this->from(route('dashboard'))
        ->post(route('walkthrough.complete'), ['action' => 'dismiss'])
        ->assertRedirect(route('dashboard'));

    expect($student->fresh()->walkthrough_completed_at)->not->toBeNull();

    $this->get(route('dashboard'))
        ->assertSee('Dashboard guide')
        ->assertSee('id="first-login-tour-dialog"', false);
});

test('faculty guided tour highlights course tools and starts the create flow', function () {
    $faculty = User::factory()->faculty()->create();

    $this->actingAs($faculty)
        ->get(route('faculty.dashboard'))
        ->assertOk()
        ->assertSee('Faculty guided tour')
        ->assertSee('Manage your courses')
        ->assertSee('data-tour-target="faculty-create-course"', false)
        ->assertSee('first-login-tour-finish');

    $this->from(route('faculty.dashboard'))
        ->post(route('walkthrough.complete'), ['action' => 'start'])
        ->assertRedirect(route('faculty.create'));

    expect($faculty->fresh()->walkthrough_completed_at)->not->toBeNull();
});

test('student starts the first-login tour at the catalog guide', function () {
    $student = User::factory()->create([
        'profile_completed' => true,
        'role_id' => User::ROLE_STUDENT,
    ]);

    $this->actingAs($student)
        ->post(route('walkthrough.complete'), ['action' => 'start'])
        ->assertRedirect(route('courses.browse', ['guide' => 1]));
});

test('admin guided tour highlights dashboard and starts course approvals', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Administrator guided tour')
        ->assertSee('See what needs action')
        ->assertSee('Jump to common tasks')
        ->assertSee('data-tour-compact', false)
        ->assertSee('data-tour-fallback="admin-welcome"', false)
        ->assertSee('completion verifications');

    $this->from(route('admin.dashboard'))
        ->post(route('walkthrough.complete'), ['action' => 'start'])
        ->assertRedirect(route('admin.courses'));

    expect($admin->fresh()->walkthrough_completed_at)->not->toBeNull();
});
