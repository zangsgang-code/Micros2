<?php

namespace App\Services;

use App\Models\AcademicCreditRecognition;
use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\LessonActivitySubmission;
use App\Models\LessonCompletion;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Carbon;

class CreditEvidenceReportService
{
    /** @return array<string, mixed> */
    public function build(AcademicCreditRecognition $recognition): array
    {
        $recognition->loadMissing(['user', 'framework']);
        $student = $recognition->user;
        abort_unless($student instanceof User, 404);

        $snapshot = $recognition->framework_snapshot ?? [];
        $frameworkSnapshot = $snapshot['framework'] ?? [];
        $framework = (object) array_merge([
            'id' => $recognition->stacking_framework_id,
            'name' => $recognition->framework?->name ?? 'Framework',
            'version_number' => 1,
            'description' => null,
            'approving_academic_unit' => null,
            'approval_reference' => null,
            'approval_date' => null,
            'effective_date' => null,
            'expiration_date' => null,
            'target_recognition' => null,
            'recognition_target_type' => null,
            'equivalent_course' => null,
            'equivalent_units' => null,
            'credit_equivalency' => null,
            'credit_recognition_conditions' => null,
            'pqf_level' => null,
            'sequence_required' => false,
            'cumulative_outcomes' => [],
        ], $frameworkSnapshot);
        $studentSnapshot = (object) array_merge([
            'name' => $student->name,
            'student_id' => $student->student_id,
            'user_code' => $student->user_code,
            'email' => $student->email,
        ], $snapshot['student'] ?? []);

        $requirements = collect($snapshot['requirements'] ?? [])
            ->sortBy('order')
            ->map(fn (array $requirement): array => $this->buildRequirementEvidence($requirement, $student))
            ->values();
        $requiredRequirements = $requirements
            ->filter(fn (array $item): bool => $item['requirement']->is_required)
            ->values();

        $savedProgress = $snapshot['progress'] ?? [];
        $progress = (object) [
            'status' => $savedProgress['status'] ?? 'requirements_met',
            'requirements_met_at' => $savedProgress['requirements_met_at'] ?? $recognition->created_at,
        ];

        return [
            'student' => $studentSnapshot,
            'framework' => $framework,
            'recognition' => $recognition,
            'requirements' => $requirements,
            'progress' => $progress,
            'generated_at' => now(),
            'request_submitted_at' => $recognition->created_at,
            'total_learning_hours' => $requiredRequirements->sum(fn (array $item): float => (float) ($item['course']->learning_hours ?? 0)),
            'total_required_courses' => $requiredRequirements->count(),
            'completed_required_courses' => $requiredRequirements->where('is_completed', true)->count(),
            'total_quiz_attempts' => $requiredRequirements->sum(fn (array $item): int => $item['quiz_attempts']->count()),
            'total_activity_submissions' => $requiredRequirements->sum(fn (array $item): int => $item['activity_submissions']->count()),
            'total_assessments' => $requiredRequirements->sum(fn (array $item): int => $item['assessments']->count()),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function buildRequirementEvidence(array $snapshot, User $student): array
    {
        $course = isset($snapshot['course_id'])
            ? Course::query()->with(['learningOutcomes', 'modules.lessons'])->find($snapshot['course_id'])
            : null;
        $courseData = (object) array_merge([
            'id' => $snapshot['course_id'] ?? null,
            'title' => $course?->title ?? 'Removed course',
            'description' => null,
            'pqf_level' => $course?->pqf_level,
            'learning_hours' => $course?->learning_hours,
            'credit_equivalency' => $course?->credit_equivalency,
        ], array_filter([
            'title' => $snapshot['title'] ?? null,
            'description' => $snapshot['description'] ?? null,
            'pqf_level' => $snapshot['pqf_level'] ?? null,
            'learning_hours' => $snapshot['learning_hours'] ?? null,
            'credit_equivalency' => $snapshot['credit_equivalency'] ?? null,
        ], fn ($value): bool => $value !== null));
        $enrollment = $course
            ? $course->enrollments()->where('user_id', $student->id)->first()
            : null;
        $liveCompletion = $enrollment?->completion_status === 'completed';
        $completedAt = $snapshot['completed_at'] ?? null;
        $lessonIds = $course?->lessons()->pluck('id') ?? collect();
        $lessonCompletions = $lessonIds->isEmpty()
            ? collect()
            : LessonCompletion::query()
                ->where('user_id', $student->id)
                ->whereIn('lesson_id', $lessonIds)
                ->get()
                ->keyBy('lesson_id');

        $modules = $course?->modules->map(function ($module) use ($lessonCompletions): array {
            return [
                'title' => $module->title,
                'lessons' => $module->lessons->map(fn ($lesson): array => [
                    'title' => $lesson->title,
                    'completed_at' => $lessonCompletions->get($lesson->id)?->completed_at,
                ])->values(),
            ];
        })->values() ?? collect();

        $quizAttempts = $course
            ? QuizAttempt::query()
                ->with('quiz')
                ->where('user_id', $student->id)
                ->whereHas('quiz', fn ($query) => $query->where('course_id', $course->id))
                ->orderBy('submitted_at')
                ->get()
            : collect();
        $activitySubmissions = $course
            ? LessonActivitySubmission::query()
                ->with('activity')
                ->where('user_id', $student->id)
                ->whereHas('activity', function ($query) use ($course): void {
                    $query->whereHas('lesson', fn ($lesson) => $lesson->where('course_id', $course->id))
                        ->orWhereHas('module', fn ($module) => $module->where('course_id', $course->id));
                })
                ->orderBy('submitted_at')
                ->get()
            : collect();
        $assessments = $course
            ? Assessment::query()
                ->with(['unit', 'level'])
                ->where('user_id', $student->id)
                ->where('course_id', $course->id)
                ->orderBy('created_at')
                ->get()
            : collect();
        $certificate = $course
            ? Certificate::query()
                ->where('user_id', $student->id)
                ->where('course_id', $course->id)
                ->latest('issued_at')
                ->first()
            : null;
        $badge = $course && $course->badge_id
            ? UserBadge::query()
                ->with('badge')
                ->where('user_id', $student->id)
                ->where('badge_id', $course->badge_id)
                ->latest('earned_at')
                ->first()
            : null;
        $savedOutcomes = collect($snapshot['outcomes'] ?? [])
            ->filter(fn ($description): bool => filled($description))
            ->map(fn ($description): object => (object) ['description' => $description]);
        $outcomes = $savedOutcomes->isNotEmpty()
            ? $savedOutcomes
            : ($course?->learningOutcomes ?? collect());

        return [
            'requirement' => (object) [
                'is_required' => (bool) ($snapshot['is_required'] ?? true),
                'order' => (int) ($snapshot['order'] ?? 0),
            ],
            'course' => $courseData,
            'enrollment' => $enrollment,
            'is_completed' => (bool) ($snapshot['is_completed'] ?? $liveCompletion),
            'completed_at' => $completedAt ? Carbon::parse($completedAt) : $enrollment?->completed_at,
            'certificate' => $certificate,
            'badge' => $badge,
            'outcomes' => $outcomes,
            'modules' => $modules,
            'quiz_attempts' => $quizAttempts,
            'activity_submissions' => $activitySubmissions,
            'assessments' => $assessments,
        ];
    }
}
