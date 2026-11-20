<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('only students see the account deletion setting', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Deactivate and delete account')
        ->assertSee(route('settings.account-deletion'), false);

    $faculty = User::factory()->faculty()->create();

    $this->actingAs($faculty)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertDontSee('Deactivate and delete account');
});

test('student account deletion requires the current password and typed confirmation', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->delete(route('settings.account-deletion'), [
            'password' => 'incorrect',
            'confirmation' => 'DELETE',
        ])
        ->assertSessionHasErrors('password');

    expect($student->fresh()->is_active)->toBeTrue()
        ->and($student->fresh()->account_deletion_requested_at)->toBeNull();

    $this->delete(route('settings.account-deletion'), [
        'password' => 'password',
        'confirmation' => 'delete',
    ])->assertSessionHasErrors('confirmation');

    expect($student->fresh()->is_active)->toBeTrue()
        ->and($student->fresh()->account_deletion_requested_at)->toBeNull();
});

test('student can reactivate a pending deletion by logging in during the 30-day window', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->delete(route('settings.account-deletion'), [
            'password' => 'password',
            'confirmation' => 'DELETE',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    expect($student->fresh()->is_active)->toBeFalse()
        ->and($student->fresh()->account_deletion_requested_at)->not->toBeNull();

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect($student->fresh()->is_active)->toBeTrue()
        ->and($student->fresh()->account_deletion_requested_at)->toBeNull();
});

test('a pending deletion cannot keep using an existing authenticated session', function () {
    $student = User::factory()->create([
        'is_active' => false,
        'account_deletion_requested_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('settings.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

test('student cannot reactivate after the 30-day period expires', function () {
    $student = User::factory()->create([
        'is_active' => false,
        'account_deletion_requested_at' => now()->subDays(30),
    ]);

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    expect($student->fresh()->is_active)->toBeFalse()
        ->and($student->fresh()->account_deletion_requested_at)->not->toBeNull();
});

test('expired student account is anonymized while learning records are preserved', function () {
    $student = User::factory()->create([
        'first_name' => 'Learner',
        'last_name' => 'Example',
        'phone' => '555-0100',
        'is_active' => false,
        'account_deletion_requested_at' => now()->subDays(31),
    ]);
    $recentRequest = User::factory()->create([
        'first_name' => 'Recent',
        'is_active' => false,
        'account_deletion_requested_at' => now()->subDays(29),
    ]);
    $course = Course::create([
        'title' => 'Preserved course',
        'slug' => Str::uuid()->toString(),
        'approval_status' => 'draft',
        'is_approved' => false,
        'is_published' => false,
    ]);
    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
    ]);

    $this->artisan('accounts:anonymize-expired-students')
        ->assertSuccessful();

    $anonymizedStudent = $student->fresh();

    expect($anonymizedStudent->first_name)->toBe('Deleted')
        ->and($anonymizedStudent->last_name)->toBe('Student')
        ->and($anonymizedStudent->phone)->toBeNull()
        ->and($anonymizedStudent->is_active)->toBeFalse()
        ->and($anonymizedStudent->account_anonymized_at)->not->toBeNull();

    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
    ]);
    expect($recentRequest->fresh()->first_name)->toBe('Recent')
        ->and($recentRequest->fresh()->account_anonymized_at)->toBeNull();
});

test('faculty cannot request student account deletion', function () {
    $faculty = User::factory()->faculty()->create();

    $this->actingAs($faculty)
        ->delete(route('settings.account-deletion'), [
            'password' => 'password',
            'confirmation' => 'DELETE',
        ])
        ->assertForbidden();

    expect($faculty->fresh()->is_active)->toBeTrue()
        ->and($faculty->fresh()->account_deletion_requested_at)->toBeNull();
});
