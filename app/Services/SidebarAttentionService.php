<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonActivitySubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

class SidebarAttentionService
{
    /**
     * @return array{
     *     admin_course_reviews: int,
     *     admin_completion_reviews: int,
     *     faculty_learner_progress: bool,
     *     faculty_my_courses: bool
     * }
     */
    public function forUser(User $user): array
    {
        $attention = [
            'admin_course_reviews' => 0,
            'admin_completion_reviews' => 0,
            'faculty_learner_progress' => false,
            'faculty_my_courses' => false,
        ];

        if ($user->isAdmin()) {
            $attention['admin_course_reviews'] = Course::query()
                ->where('approval_status', 'pending')
                ->count();
            $attention['admin_completion_reviews'] = Enrollment::query()
                ->where('completion_status', MicrocredentialCompletionService::STATUS_AWAITING_FACULTY_VERIFICATION)
                ->where('academic_unit_confirmation_status', 'pending')
                ->whereHas('course.reviews', fn ($query) => $query->whereColumn('course_reviews.user_id', 'enrollments.user_id'))
                ->count();
        }

        if (! $user->isFaculty()) {
            return $attention;
        }

        $courseIds = Course::query()
            ->where('created_by', $user->id)
            ->pluck('id');

        if ($courseIds->isEmpty()) {
            return $attention;
        }

        $attention['faculty_my_courses'] = LessonActivitySubmission::query()
            ->where('status', 'submitted')
            ->whereHas('activity', function ($query) use ($courseIds): void {
                $query->where('is_active', true)
                    ->where('activity_type', 'assignment')
                    ->where(function ($scope) use ($courseIds): void {
                        $scope->whereHas('lesson', fn ($lesson) => $lesson->whereIn('course_id', $courseIds))
                            ->orWhereHas('module', fn ($module) => $module->whereIn('course_id', $courseIds));
                    });
            })
            ->exists();

        $hasPendingReview = Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->whereIn('faculty_verification_status', ['pending', 'not_required'])
            ->where('completion_status', MicrocredentialCompletionService::STATUS_AWAITING_FACULTY_VERIFICATION)
            ->whereHas('course', fn ($query) => $query->where('requires_faculty_verification', true))
            ->exists();

        $attention['faculty_learner_progress'] = $hasPendingReview;

        if ($hasPendingReview) {
            return $attention;
        }

        $studentIds = Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->distinct()
            ->pluck('user_id');
        $attemptPairs = QuizAttempt::query()
            ->whereIn('user_id', $studentIds)
            ->whereHas('quiz', fn ($query) => $query->whereIn('course_id', $courseIds)->whereHas('questions'))
            ->whereHas('answers')
            ->select(['user_id', 'quiz_id'])
            ->distinct()
            ->get();

        if ($attemptPairs->isEmpty()) {
            return $attention;
        }

        $quizzes = Quiz::query()
            ->with('questions')
            ->whereIn('id', $attemptPairs->pluck('quiz_id')->unique())
            ->get()
            ->keyBy('id');
        $attemptService = app(QuizAttemptService::class);

        $attention['faculty_learner_progress'] = $attemptPairs->contains(
            fn (QuizAttempt $attempt): bool => isset($quizzes[$attempt->quiz_id])
                && $attemptService->retakeStatus($quizzes[$attempt->quiz_id], (int) $attempt->user_id)['exhausted']
        );

        return $attention;
    }
}
