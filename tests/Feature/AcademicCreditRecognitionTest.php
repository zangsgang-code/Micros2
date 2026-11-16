<?php

use App\Models\AcademicCreditRecognition;
use App\Models\Assessment;
use App\Models\CompetencyCategory;
use App\Models\CompetencyUnit;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LearningOutcome;
use App\Models\LessonActivity;
use App\Models\LessonActivitySubmission;
use App\Models\LessonCompletion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StackingFramework;
use App\Models\StackingFrameworkRequirement;
use App\Models\User;
use App\Services\AcademicCreditRecognitionService;
use App\Services\CreditEvidenceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeRecognitionCourse(string $name): Course
{
    $faculty = User::factory()->faculty()->create();

    return Course::create([
        'title' => $name,
        'slug' => str()->slug($name).'-'.uniqid(),
        'created_by' => $faculty->id,
        'is_published' => true,
        'approval_status' => 'approved',
        'is_approved' => true,
        'certificate_enabled' => true,
        'pqf_level' => '5',
        'learning_hours' => 12,
    ]);
}

function makeRecognitionFramework(Course $course, array $attributes = []): StackingFramework
{
    $framework = StackingFramework::create(array_merge([
        'name' => 'Recognition Framework '.uniqid(),
        'description' => 'Approved group of microcredentials.',
        'status' => 'approved',
        'is_active' => true,
        'required_count' => 1,
        'target_recognition' => 'Information Technology elective requirement',
        'recognition_target_type' => 'elective_requirement',
        'approving_academic_unit' => 'College of Computing',
        'approval_reference' => 'PSU-RECOGNITION-01',
        'approval_date' => today()->subDay(),
        'effective_date' => today(),
        'equivalent_course' => 'BSIT 301',
        'equivalent_units' => 6,
        'credit_equivalency' => 'For institutional review',
        'credit_recognition_conditions' => 'Review the supporting course records.',
        'sequence_required' => false,
        'cumulative_outcomes' => ['Apply the combined competencies.'],
    ], $attributes));

    StackingFrameworkRequirement::create([
        'stacking_framework_id' => $framework->id,
        'course_id' => $course->id,
        'order' => 1,
        'is_required' => true,
    ]);

    return $framework->fresh('requirements.course');
}

function completeRecognitionCourse(User $user, Course $course): Enrollment
{
    return Enrollment::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'completion_status' => 'completed',
        'progress_percent' => 100,
        'is_completed' => true,
        'completed_at' => now()->subDay(),
    ]);
}

test('student cannot request an evidence report while a required course is incomplete', function () {
    $user = User::factory()->create();
    $course = makeRecognitionCourse('In Progress Course');
    $framework = makeRecognitionFramework($course);
    Enrollment::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'completion_status' => 'in_progress',
        'progress_percent' => 100,
        'is_completed' => true,
    ]);

    expect(app(AcademicCreditRecognitionService::class)->canRequestRecognition($user, $framework))->toBeFalse();

    $this->actingAs($user)
        ->post(route('stacking.requests.store', $framework->id))
        ->assertRedirect()
        ->assertSessionHasErrors('recognition');

    expect(AcademicCreditRecognition::count())->toBe(0);
});

test('student can submit one request after official completion and the framework version is snapshotted', function () {
    $user = User::factory()->create();
    $course = makeRecognitionCourse('Ready Course');
    $framework = makeRecognitionFramework($course);
    completeRecognitionCourse($user, $course);

    $record = app(AcademicCreditRecognitionService::class)->requestRecognition($user, $framework, 'Student request');

    expect($record->status)->toBe('submitted')
        ->and($record->user_id)->toBe($user->id)
        ->and($record->stacking_framework_id)->toBe($framework->id)
        ->and(data_get($record->framework_snapshot, 'framework.version_number'))->toBe(1)
        ->and(data_get($record->framework_snapshot, 'framework.name'))->toBe($framework->name)
        ->and(data_get($record->framework_snapshot, 'requirements.0.is_completed'))->toBeTrue()
        ->and(data_get($record->framework_snapshot, 'requirements.0.completed_at'))->not->toBeNull();
});

test('student can submit the recognition request from the progress page after all courses are complete', function () {
    $student = User::factory()->create();
    $course = makeRecognitionCourse('Request Page Course');
    $framework = makeRecognitionFramework($course);
    completeRecognitionCourse($student, $course);

    $this->actingAs($student)
        ->post(route('stacking.requests.store', $framework->id), ['notes' => 'Please review my records.'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->get(route('stacking.progress'))
        ->assertOk()
        ->assertSee('Your request is waiting for internal UPSKILL review.');

    expect(AcademicCreditRecognition::firstOrFail()->status)->toBe('submitted');
});

test('every required microcredential must be officially completed before requesting a report', function () {
    $user = User::factory()->create();
    $first = makeRecognitionCourse('Required One');
    $second = makeRecognitionCourse('Required Two');
    $framework = makeRecognitionFramework($first);
    StackingFrameworkRequirement::create([
        'stacking_framework_id' => $framework->id,
        'course_id' => $second->id,
        'order' => 2,
        'is_required' => true,
    ]);
    $framework = $framework->fresh('requirements.course');
    completeRecognitionCourse($user, $first);

    expect(app(AcademicCreditRecognitionService::class)->canRequestRecognition($user, $framework))->toBeFalse();

    completeRecognitionCourse($user, $second);
    expect(app(AcademicCreditRecognitionService::class)->canRequestRecognition($user, $framework))->toBeTrue();
});

test('duplicate requests for a framework version are prevented', function () {
    $user = User::factory()->create();
    $course = makeRecognitionCourse('Duplicate Course');
    $framework = makeRecognitionFramework($course);
    completeRecognitionCourse($user, $course);
    $service = app(AcademicCreditRecognitionService::class);
    $service->requestRecognition($user, $framework);

    expect(fn () => $service->requestRecognition($user, $framework))
        ->toThrow(InvalidArgumentException::class, 'already has a request');
});

test('admins can use only internal request statuses and cannot record external decisions', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $course = makeRecognitionCourse('Review Course');
    $framework = makeRecognitionFramework($course);
    completeRecognitionCourse($user, $course);
    $record = app(AcademicCreditRecognitionService::class)->requestRecognition($user, $framework);
    $service = app(AcademicCreditRecognitionService::class);

    $record = $service->updateInternalStatus($record, $admin, 'under_review', 'Reviewing the supporting records.');
    expect($record->status)->toBe('under_review')
        ->and($record->reviewed_by)->toBe($admin->id)
        ->and($record->unit_reviewed_by)->toBeNull()
        ->and($record->dean_endorsed_by)->toBeNull()
        ->and($record->registrar_recorded_by)->toBeNull();

    expect(fn () => $service->updateInternalStatus($record, $admin, 'registrar_recorded'))
        ->toThrow(InvalidArgumentException::class, 'Only internal review statuses');
});

test('student may respond to an additional-information request but cannot alter another student request', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $otherStudent = User::factory()->create();
    $course = makeRecognitionCourse('Response Course');
    $framework = makeRecognitionFramework($course);
    completeRecognitionCourse($user, $course);
    $service = app(AcademicCreditRecognitionService::class);
    $record = $service->requestRecognition($user, $framework);
    $record = $service->updateInternalStatus($record, $admin, 'needs_information', 'Please add a note.');

    $this->actingAs($otherStudent)
        ->post(route('stacking.requests.response', $record->id), ['student_response' => 'Response'])
        ->assertNotFound();

    $this->actingAs($user)
        ->post(route('stacking.requests.response', $record->id), ['student_response' => 'Additional detail'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($record->fresh()->status)->toBe('submitted')
        ->and($record->fresh()->student_response)->toBe('Additional detail');
});

test('admin can search and review a request while students cannot access admin review', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $course = makeRecognitionCourse('Admin Review Course');
    $framework = makeRecognitionFramework($course);
    completeRecognitionCourse($student, $course);
    $record = app(AcademicCreditRecognitionService::class)->requestRecognition($student, $framework);

    $this->actingAs($admin)
        ->get(route('admin.academic-credit-recognition', ['search' => $student->email, 'status' => 'submitted']))
        ->assertOk()
        ->assertSee($framework->name)
        ->assertSee('Review request');

    $this->actingAs($admin)
        ->get(route('admin.academic-credit-recognition.show', $record->id))
        ->assertOk()
        ->assertSee('Required course completion and learning outcomes')
        ->assertSee('Open course record');

    $this->actingAs($admin)
        ->post(route('admin.academic-credit-recognition.status', $record->id), [
            'status' => 'under_review',
            'review_notes' => 'Reviewing the course evidence.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($record->fresh()->status)->toBe('under_review')
        ->and($record->fresh()->review_notes)->toBe('Reviewing the course evidence.');

    $this->actingAs($student)
        ->get(route('admin.academic-credit-recognition.show', $record->id))
        ->assertRedirect();
});

test('admin can download a PDF after moving the request to under review', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $course = makeRecognitionCourse('PDF Evidence Course');
    $framework = makeRecognitionFramework($course);
    completeRecognitionCourse($student, $course);
    $record = app(AcademicCreditRecognitionService::class)->requestRecognition($student, $framework);

    $this->actingAs($admin)
        ->get(route('admin.credit-evidence', $record->id))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('admin.credit-evidence', $record->id))
        ->assertRedirect();

    app(AcademicCreditRecognitionService::class)->updateInternalStatus($record, $admin, 'under_review');

    $response = $this->actingAs($admin)->get(route('admin.credit-evidence', $record->id));
    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename="UPSKILL-Evidence-Request-'.$record->id.'.pdf"');

    expect(substr($response->getContent(), 0, 4))->toBe('%PDF')
        ->and($record->fresh()->status)->toBe('report_generated')
        ->and($record->fresh()->report_generated_by)->toBe($admin->id)
        ->and($record->fresh()->report_generated_at)->not->toBeNull();
});

test('evidence report gathers learning outcomes, lesson, quiz, activity, and course assessment records', function () {
    $student = User::factory()->create();
    $course = makeRecognitionCourse('Detailed Evidence Course');
    $module = CourseModule::create([
        'course_id' => $course->id,
        'title' => 'Module One',
        'order' => 1,
    ]);
    $lesson = CourseLesson::create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'title' => 'Lesson One',
        'type' => 'Reading',
        'order' => 1,
        'module_order' => 1,
    ]);
    LearningOutcome::create([
        'course_id' => $course->id,
        'code' => 'LO1',
        'description' => 'Build a secure application.',
        'order' => 1,
    ]);
    LessonCompletion::create([
        'user_id' => $student->id,
        'lesson_id' => $lesson->id,
        'completed_at' => now()->subHours(3),
    ]);
    $quiz = Quiz::create([
        'course_id' => $course->id,
        'module_id' => $module->id,
        'lesson_id' => $lesson->id,
        'title' => 'Module Quiz',
        'passing_score' => 70,
        'attempts' => 2,
    ]);
    QuizAttempt::create([
        'user_id' => $student->id,
        'quiz_id' => $quiz->id,
        'score' => 90,
        'passed' => true,
        'started_at' => now()->subHours(2),
        'submitted_at' => now()->subHour(),
    ]);
    $activity = LessonActivity::create([
        'lesson_id' => $lesson->id,
        'module_id' => $module->id,
        'title' => 'Secure design exercise',
        'activity_type' => 'practice',
    ]);
    LessonActivitySubmission::create([
        'lesson_activity_id' => $activity->id,
        'user_id' => $student->id,
        'status' => 'reviewed',
        'score' => 8,
        'submitted_at' => now()->subMinutes(50),
        'reviewed_at' => now()->subMinutes(20),
    ]);
    $category = CompetencyCategory::create(['name' => 'Software Development']);
    $unit = CompetencyUnit::create([
        'competency_category_id' => $category->id,
        'title' => 'Secure application development',
    ]);
    Assessment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'competency_unit_id' => $unit->id,
        'type' => 'final',
        'title' => 'Final practical assessment',
        'status' => 'passed',
        'score' => 95,
        'reviewed_at' => now()->subMinutes(10),
    ]);

    completeRecognitionCourse($student, $course);
    $framework = makeRecognitionFramework($course);
    $recognition = app(AcademicCreditRecognitionService::class)->requestRecognition($student, $framework);
    $evidence = app(CreditEvidenceReportService::class)->build($recognition);
    $requirement = $evidence['requirements']->first();

    expect($requirement['outcomes']->first()->description)->toBe('Build a secure application.')
        ->and($requirement['modules']->first()['lessons']->first()['completed_at'])->not->toBeNull()
        ->and($requirement['quiz_attempts']->first()->score)->toBe(90)
        ->and($requirement['activity_submissions']->first()->score)->toBe(8)
        ->and($requirement['assessments']->first()->score)->toBe(95)
        ->and($evidence['total_learning_hours'])->toBe(12.0)
        ->and($evidence['completed_required_courses'])->toBe(1)
        ->and($evidence['total_quiz_attempts'])->toBe(1)
        ->and($evidence['total_activity_submissions'])->toBe(1)
        ->and($evidence['total_assessments'])->toBe(1);
});
