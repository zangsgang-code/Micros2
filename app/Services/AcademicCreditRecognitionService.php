<?php

namespace App\Services;

use App\Models\AcademicCreditRecognition;
use App\Models\StackingFramework;
use App\Models\User;
use App\Models\UserStackingProgress;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AcademicCreditRecognitionService
{
    public function __construct(private StackingProgressService $stackingProgress) {}

    public function canRequestRecognition(User $user, StackingFramework $framework): bool
    {
        if (! $framework->isAvailableToStudents()) {
            return false;
        }

        $progress = $this->stackingProgress->syncFrameworkForUser($framework, (int) $user->id);

        return $progress?->status === 'requirements_met';
    }

    public function requestRecognition(User $user, StackingFramework $framework, ?string $notes = null): AcademicCreditRecognition
    {
        if (! $this->canRequestRecognition($user, $framework)) {
            throw new InvalidArgumentException('All required microcredentials must be officially completed before requesting an evidence report.');
        }

        return DB::transaction(function () use ($user, $framework, $notes): AcademicCreditRecognition {
            StackingFramework::query()->whereKey($framework->id)->lockForUpdate()->firstOrFail();

            $existing = AcademicCreditRecognition::query()
                ->where('user_id', $user->id)
                ->where('stacking_framework_id', $framework->id)
                ->exists();

            if ($existing) {
                throw new InvalidArgumentException('This student already has a request for this framework version.');
            }

            return AcademicCreditRecognition::create([
                'user_id' => $user->id,
                'stacking_framework_id' => $framework->id,
                'status' => 'submitted',
                'notes' => $notes,
                'framework_snapshot' => $this->frameworkSnapshot($user, $framework),
            ]);
        });
    }

    public function updateInternalStatus(
        AcademicCreditRecognition $recognition,
        User $reviewer,
        string $status,
        ?string $reviewNotes = null
    ): AcademicCreditRecognition {
        if (! in_array($status, ['under_review', 'needs_information'], true)) {
            throw new InvalidArgumentException('Only internal review statuses may be selected.');
        }

        if (! in_array($recognition->status, ['submitted', 'under_review', 'needs_information'], true)) {
            throw new InvalidArgumentException('This request is not open for internal review.');
        }

        $recognition->update([
            'status' => $status,
            'review_notes' => $reviewNotes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $recognition->fresh();
    }

    public function submitStudentResponse(
        AcademicCreditRecognition $recognition,
        User $student,
        string $response
    ): AcademicCreditRecognition {
        if ((int) $recognition->user_id !== (int) $student->id) {
            throw new InvalidArgumentException('This request does not belong to the signed-in student.');
        }

        if ($recognition->status !== 'needs_information') {
            throw new InvalidArgumentException('This request is not waiting for additional information.');
        }

        $recognition->update([
            'status' => 'submitted',
            'student_response' => $response,
        ]);

        return $recognition->fresh();
    }

    public function markReportGenerated(AcademicCreditRecognition $recognition, User $admin): AcademicCreditRecognition
    {
        if (! in_array($recognition->status, ['under_review', 'report_generated'], true)) {
            throw new InvalidArgumentException('Move this request to Under Review before generating its report.');
        }

        $recognition->update([
            'status' => 'report_generated',
            'report_generated_by' => $admin->id,
            'report_generated_at' => now(),
        ]);

        return $recognition->fresh();
    }

    /** @return array<string, mixed> */
    private function frameworkSnapshot(User $student, StackingFramework $framework): array
    {
        $framework->loadMissing(['requirements.course.learningOutcomes']);
        $progress = UserStackingProgress::query()
            ->where('user_id', $student->id)
            ->where('stacking_framework_id', $framework->id)
            ->first();

        return [
            'student' => [
                'name' => $student->name,
                'student_id' => $student->student_id,
                'user_code' => $student->user_code,
                'email' => $student->email,
            ],
            'progress' => [
                'status' => 'requirements_met',
                'requirements_met_at' => $progress?->requirements_met_at?->toIso8601String(),
            ],
            'framework' => [
                'id' => $framework->id,
                'name' => $framework->name,
                'version_number' => $framework->version_number,
                'description' => $framework->description,
                'approving_academic_unit' => $framework->approving_academic_unit,
                'approval_reference' => $framework->approval_reference,
                'approval_date' => $framework->approval_date?->toDateString(),
                'effective_date' => $framework->effective_date?->toDateString(),
                'expiration_date' => $framework->expiration_date?->toDateString(),
                'target_recognition' => $framework->target_recognition,
                'recognition_target_type' => $framework->recognition_target_type,
                'equivalent_course' => $framework->equivalent_course,
                'equivalent_units' => $framework->equivalent_units,
                'credit_equivalency' => $framework->credit_equivalency,
                'credit_recognition_conditions' => $framework->credit_recognition_conditions,
                'pqf_level' => $framework->pqf_level,
                'sequence_required' => (bool) $framework->sequence_required,
                'cumulative_outcomes' => $framework->cumulative_outcomes ?? [],
            ],
            'requirements' => $framework->requirements->sortBy('order')->map(function ($requirement) use ($student): array {
                $course = $requirement->course;
                $enrollment = $course?->enrollments()
                    ->where('user_id', $student->id)
                    ->first();

                return [
                    'course_id' => $requirement->course_id,
                    'title' => $course?->title ?? 'Removed course',
                    'description' => $course?->description,
                    'pqf_level' => $course?->pqf_level,
                    'learning_hours' => $course?->learning_hours,
                    'credit_equivalency' => $course?->credit_equivalency,
                    'is_required' => (bool) $requirement->is_required,
                    'order' => $requirement->order,
                    'is_completed' => $enrollment?->completion_status === 'completed',
                    'completed_at' => $enrollment?->completed_at?->toIso8601String(),
                    'outcomes' => ($course?->learningOutcomes ?? collect())->pluck('description')->all(),
                ];
            })->values()->all(),
        ];
    }
}
