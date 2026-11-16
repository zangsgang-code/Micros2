<?php

use App\Models\AcademicCreditRecognition;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StackingFramework;
use App\Models\StackingFrameworkRequirement;
use App\Models\User;
use App\Services\AcademicCreditRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeStackingAdminCourse(string $suffix, bool $microcredential = true): Course
{
    $faculty = User::factory()->faculty()->create();

    return Course::create([
        'title' => 'Microcredential '.$suffix,
        'slug' => 'microcredential-'.strtolower($suffix).'-'.uniqid(),
        'created_by' => $faculty->id,
        'is_published' => true,
        'approval_status' => 'approved',
        'is_approved' => true,
        'certificate_enabled' => $microcredential,
    ]);
}

function stackingFrameworkPayload(array $requirements = []): array
{
    return [
        'name' => 'Web Technology Stack',
        'description' => 'Approved group of microcredentials.',
        'pqf_level' => '5',
        'target_recognition' => 'Diploma elective requirement',
        'recognition_target_type' => 'elective_requirement',
        'equivalent_course' => 'BSIT 301',
        'equivalent_units' => '6',
        'credit_equivalency' => 'For institutional evaluation',
        'credit_recognition_conditions' => 'See the recorded institutional approval.',
        'approving_academic_unit' => 'College of Computing',
        'approval_reference' => 'PSU-ACADEMIC-2026-04',
        'approval_date' => today()->subDay()->toDateString(),
        'effective_date' => today()->toDateString(),
        'expiration_date' => '',
        'sequence_required' => '1',
        'cumulative_outcomes' => [
            'Design and build a secure web application.',
            'Evaluate the application against documented requirements.',
        ],
        'requirements' => $requirements,
    ];
}

test('admin can view the stacking framework list', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.stacking-frameworks'))
        ->assertOk()
        ->assertViewIs('admin.stacking-frameworks');
});

test('admin can create a draft with individually authored outcomes and required courses', function () {
    $admin = User::factory()->admin()->create();
    $first = makeStackingAdminCourse('One');
    $second = makeStackingAdminCourse('Two');

    $response = $this->actingAs($admin)->post(route('admin.stacking-frameworks.store'), stackingFrameworkPayload([
        ['course_id' => $first->id, 'order' => 1, 'is_required' => 0],
        ['course_id' => $second->id, 'order' => 2, 'is_required' => 0],
    ]));

    $response->assertRedirect(route('admin.stacking-frameworks'));
    $framework = StackingFramework::with('requirements')->firstOrFail();

    expect($framework->status)->toBe('draft')
        ->and($framework->is_active)->toBeFalse()
        ->and($framework->completion_mode)->toBe('all_required')
        ->and($framework->required_count)->toBe(2)
        ->and($framework->version_number)->toBe(1)
        ->and($framework->approval_reference)->toBe('PSU-ACADEMIC-2026-04')
        ->and($framework->cumulative_outcomes)->toHaveCount(2)
        ->and($framework->requirements)->toHaveCount(2)
        ->and($framework->requirements[0]->course_id)->toBe($first->id)
        ->and($framework->requirements[0]->is_required)->toBeTrue()
        ->and($framework->requirements[1]->course_id)->toBe($second->id)
        ->and($framework->requirements[1]->is_required)->toBeTrue();
});

test('framework draft requires outcomes and at least one approved microcredential', function () {
    $admin = User::factory()->admin()->create();
    $payload = stackingFrameworkPayload();
    $payload['equivalent_units'] = 'six';
    $payload['cumulative_outcomes'] = [];

    $this->actingAs($admin)
        ->from(route('admin.stacking-frameworks'))
        ->post(route('admin.stacking-frameworks.store'), $payload)
        ->assertRedirect(route('admin.stacking-frameworks'))
        ->assertSessionHasErrors(['equivalent_units', 'cumulative_outcomes', 'requirements']);
});

test('duplicate and non-microcredential course requirements are rejected', function () {
    $admin = User::factory()->admin()->create();
    $course = makeStackingAdminCourse('Duplicate');
    $ordinaryCourse = makeStackingAdminCourse('Ordinary', false);
    $ordinaryCourse->update(['certificate_enabled' => false]);

    $this->actingAs($admin)
        ->from(route('admin.stacking-frameworks'))
        ->post(route('admin.stacking-frameworks.store'), stackingFrameworkPayload([
            ['course_id' => $course->id, 'order' => 1],
            ['course_id' => $course->id, 'order' => 2],
        ]))
        ->assertRedirect(route('admin.stacking-frameworks'))
        ->assertSessionHasErrors('requirements');

    $this->actingAs($admin)
        ->from(route('admin.stacking-frameworks'))
        ->post(route('admin.stacking-frameworks.store'), stackingFrameworkPayload([
            ['course_id' => $ordinaryCourse->id, 'order' => 1],
        ]))
        ->assertRedirect(route('admin.stacking-frameworks'))
        ->assertSessionHasErrors('requirements');
});

test('framework cannot be activated without its institutional approval reference and dates', function () {
    $admin = User::factory()->admin()->create();
    $course = makeStackingAdminCourse('Missing Approval');
    $payload = stackingFrameworkPayload([
        ['course_id' => $course->id, 'order' => 1],
    ]);
    $payload['approval_reference'] = '';
    $payload['approval_date'] = '';
    $payload['effective_date'] = '';

    $this->actingAs($admin)->post(route('admin.stacking-frameworks.store'), $payload)->assertRedirect();
    $framework = StackingFramework::firstOrFail();

    $this->actingAs($admin)
        ->from(route('admin.stacking-frameworks'))
        ->post(route('admin.stacking-frameworks.approve', $framework->id))
        ->assertRedirect(route('admin.stacking-frameworks'))
        ->assertSessionHasErrors('approval_reference');

    expect($framework->fresh()->status)->toBe('draft')
        ->and($framework->fresh()->is_active)->toBeFalse();
});

test('framework cannot be activated without at least one required microcredential', function () {
    $admin = User::factory()->admin()->create();
    $framework = StackingFramework::create([
        'name' => 'Empty framework',
        'status' => 'draft',
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.stacking-frameworks'))
        ->post(route('admin.stacking-frameworks.approve', $framework->id))
        ->assertRedirect(route('admin.stacking-frameworks'))
        ->assertSessionHasErrors('requirements');

    expect($framework->fresh()->status)->toBe('draft')
        ->and($framework->fresh()->is_active)->toBeFalse();
});

test('admin can activate a valid documented framework without creating academic credit', function () {
    $admin = User::factory()->admin()->create();
    $course = makeStackingAdminCourse('Activate');
    $payload = stackingFrameworkPayload([
        ['course_id' => $course->id, 'order' => 1],
    ]);
    $this->actingAs($admin)->post(route('admin.stacking-frameworks.store'), $payload)->assertRedirect();
    $framework = StackingFramework::firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.stacking-frameworks.approve', $framework->id))
        ->assertRedirect(route('admin.stacking-frameworks'));

    $fresh = $framework->fresh();
    expect($fresh->status)->toBe('approved')
        ->and($fresh->is_active)->toBeTrue()
        ->and($fresh->approved_by)->toBe($admin->id)
        ->and($fresh->approved_at)->not->toBeNull()
        ->and(DB::table('academic_credit_recognitions')->count())->toBe(0);
});

test('approved framework structure cannot be edited without deactivation', function () {
    $admin = User::factory()->admin()->create();
    $framework = StackingFramework::create([
        'name' => 'Locked Framework',
        'description' => 'An active framework.',
        'status' => 'approved',
        'approval_reference' => 'PSU-LOCKED-01',
        'approval_date' => today()->subDay(),
        'effective_date' => today(),
        'approved_by' => $admin->id,
        'approved_at' => now(),
        'completion_mode' => 'all_required',
        'required_count' => 1,
        'is_active' => true,
    ]);
    $course = makeStackingAdminCourse('Locked');
    StackingFrameworkRequirement::create([
        'stacking_framework_id' => $framework->id,
        'course_id' => $course->id,
        'order' => 1,
        'is_required' => true,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.stacking-frameworks'))
        ->patch(route('admin.stacking-frameworks.update', $framework->id), stackingFrameworkPayload([
            ['course_id' => $course->id, 'order' => 1],
        ]))
        ->assertRedirect(route('admin.stacking-frameworks'))
        ->assertSessionHasErrors('framework');

    expect($framework->fresh()->name)->toBe('Locked Framework');
});

test('editing an archived framework creates a version and preserves the previous requirements', function () {
    $admin = User::factory()->admin()->create();
    $first = makeStackingAdminCourse('Version One');
    $second = makeStackingAdminCourse('Version Two');
    $framework = StackingFramework::create([
        'name' => 'Versioned Framework',
        'description' => 'Original approved version.',
        'status' => 'approved',
        'approving_academic_unit' => 'College of Computing',
        'approval_reference' => 'PSU-V1',
        'approval_date' => today()->subDays(3),
        'effective_date' => today()->subDays(2),
        'approved_by' => $admin->id,
        'approved_at' => now(),
        'target_recognition' => 'Original requirement',
        'recognition_target_type' => 'course_requirement',
        'completion_mode' => 'all_required',
        'required_count' => 1,
        'cumulative_outcomes' => ['Original outcome'],
        'is_active' => true,
    ]);
    StackingFrameworkRequirement::create([
        'stacking_framework_id' => $framework->id,
        'course_id' => $first->id,
        'order' => 1,
        'is_required' => true,
    ]);
    $student = User::factory()->create();
    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $first->id,
        'completion_status' => 'completed',
        'completed_at' => now()->subDay(),
        'is_completed' => true,
        'progress_percent' => 100,
    ]);
    $recognition = app(AcademicCreditRecognitionService::class)->requestRecognition($student, $framework);

    $this->actingAs($admin)->post(route('admin.stacking-frameworks.deactivate', $framework->id))->assertRedirect();

    $payload = stackingFrameworkPayload([
        ['course_id' => $second->id, 'order' => 1],
    ]);
    $payload['name'] = 'Versioned Framework Revised';
    $payload['cumulative_outcomes'] = ['Updated outcome'];
    $this->actingAs($admin)->patch(route('admin.stacking-frameworks.update', $framework->id), $payload)->assertRedirect();

    $draft = StackingFramework::where('previous_version_id', $framework->id)->with('requirements')->firstOrFail();
    $original = $framework->fresh('requirements');

    expect($original->status)->toBe('inactive')
        ->and($original->name)->toBe('Versioned Framework')
        ->and($original->requirements->first()->course_id)->toBe($first->id)
        ->and($draft->status)->toBe('draft')
        ->and($draft->version_number)->toBe(2)
        ->and($draft->requirements->first()->course_id)->toBe($second->id)
        ->and(AcademicCreditRecognition::findOrFail($recognition->id)->framework_snapshot['framework']['name'])->toBe('Versioned Framework')
        ->and(AcademicCreditRecognition::findOrFail($recognition->id)->framework_snapshot['requirements'][0]['course_id'])->toBe($first->id);

    $this->actingAs($admin)
        ->from(route('admin.stacking-frameworks'))
        ->patch(route('admin.stacking-frameworks.update', $framework->id), $payload)
        ->assertRedirect(route('admin.stacking-frameworks'))
        ->assertSessionHasErrors('framework');
});

test('non-admin users cannot access framework administration', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.stacking-frameworks'))
        ->assertRedirect();
});
