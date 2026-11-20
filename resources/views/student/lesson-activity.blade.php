@php
    $latestSubmission = $submissions->first();
    $completionTarget = $activity->lesson ? 'lesson' : 'module';
@endphp
<x-layout title="{{ $activity->title }} | UpSkill" shell="bare" page="lesson-activity">
    <section class="activity-shell">
        <a class="activity-back-link" href="{{ route('courses.learn', $course->id) }}">
            <span aria-hidden="true">←</span> Back to course
        </a>

        <section class="activity-card activity-overview">
            <p class="activity-eyebrow">Learning activity</p>
            <p class="activity-context">
                <span>{{ $course->title }}</span>
                <span aria-hidden="true">›</span>
                <span>{{ $activity->lesson?->title ?? $activity->module?->title }}</span>
            </p>

            <header class="activity-heading">
                <div>
                    <h1>{{ $activity->title }}</h1>
                    <div class="activity-meta">
                        <span>{{ Illuminate\Support\Str::headline($activity->activity_type) }}</span>
                        <span>{{ $activity->is_required ? 'Required for '.$completionTarget.' completion' : 'Optional activity' }}</span>
                    </div>
                </div>
                @if($latestSubmission)
                    <span class="activity-status-pill is-{{ Illuminate\Support\Str::slug($latestSubmission->status) }}">
                        {{ Illuminate\Support\Str::headline($latestSubmission->status) }}
                    </span>
                @endif
            </header>

            @if($activity->instructions)
                <section class="activity-instructions">
                    <h2>Instructions</h2>
                    <div class="up-ckeditor-wrapper">
                        <div class="ck-content rich-content">{!! \App\Support\RichTextSanitizer::sanitize($activity->instructions) !!}</div>
                    </div>
                </section>
            @endif

            @if(session('activity_result'))
                @php($activityResult = session('activity_result'))
                <div class="activity-feedback activity-feedback--{{ $activityResult['lesson_completed'] ? 'success' : ($activityResult['pending_review'] ? 'pending' : 'info') }}" role="status">
                    <strong>
                        @if($activityResult['lesson_completed'])
                            Lesson complete
                        @elseif($activityResult['pending_review'])
                            Assignment submitted
                        @else
                            Response submitted
                        @endif
                    </strong>
                    <p>{{ $activityResult['message'] }}</p>
                    @if($activityResult['pending_review'])
                        <p>Your instructor will review your assignment. The result will appear here.</p>
                    @endif
                    @if($activityResult['next_requirement_url'])
                        <a href="{{ $activityResult['next_requirement_url'] }}">Continue to the next activity or assessment</a>
                    @endif
                    <a href="{{ route('courses.learn', $course->id) }}">Return to course</a>
                </div>
            @elseif($lesson_completed)
                <div class="activity-feedback activity-feedback--success" role="status">
                    <strong>Lesson complete</strong>
                    <p>You have completed this lesson.</p>
                    <a href="{{ route('courses.learn', $course->id) }}">Return to course</a>
                </div>
            @elseif($latestSubmission?->status === 'submitted')
                <div class="activity-feedback activity-feedback--pending" role="status">
                    <strong>Awaiting review</strong>
                    <p>Your instructor will review this assignment. The {{ $completionTarget }} will complete automatically if it passes.</p>
                </div>
            @elseif($latestSubmission?->status === 'passed')
                <div class="activity-feedback activity-feedback--success" role="status">
                    <strong>Assignment passed</strong>
                    <p>Complete any remaining required activities or assessments to finish this {{ $completionTarget }}.</p>
                    <a href="{{ route('courses.learn', $course->id) }}">Return to course</a>
                </div>
            @elseif($latestSubmission?->status === 'needs_revision')
                <div class="activity-feedback activity-feedback--warning" role="status">
                    <strong>Revision requested</strong>
                    <p>Review your instructor’s feedback below, update your response, and submit it again.</p>
                </div>
            @elseif($activity->activity_type === 'assignment')
                <div class="activity-feedback activity-feedback--info">
                    <strong>Before you submit</strong>
                    <p>Your instructor will review your assignment. A passing result is required for this {{ $completionTarget }}.</p>
                </div>
            @endif

            @if($errors->any())
                <div class="activity-error" role="alert">{{ $errors->first() }}</div>
            @endif

            @if(! $lesson_completed && $latestSubmission?->status !== 'passed' && $latestSubmission?->status !== 'submitted')
                <form class="activity-form" method="POST" action="{{ route('lesson-activities.submit', $activity->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="activity-form-heading">
                        <h2>{{ $latestSubmission?->status === 'needs_revision' ? 'Update your response' : 'Your response' }}</h2>
                        <p>{{ $latestSubmission?->status === 'needs_revision' ? 'Make the requested changes and submit your updated work.' : 'Write your response below or attach a file.' }}</p>
                    </div>
                    <label for="response_text">Written response <span>Optional if you attach a file</span></label>
                    <textarea class="activity-input" id="response_text" name="response_text" rows="7" maxlength="20000">{{ old('response_text') }}</textarea>
                    <label for="submission_file">Attach a file <span>Optional · PDF, Office, image, text, or ZIP · 10 MB max</span></label>
                    <input type="file" id="submission_file" name="submission_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.png,.jpg,.jpeg,.zip">
                    <button class="activity-submit" type="submit">Submit {{ $activity->activity_type === 'assignment' ? 'assignment' : 'activity' }}</button>
                </form>
            @endif
        </section>

        @if($submissions->isNotEmpty())
            <section class="activity-card activity-history">
                <header class="activity-history-heading">
                    <div>
                        <h2>Your attempts</h2>
                        <p>Review your submitted work and instructor feedback.</p>
                    </div>
                    <span>{{ $submissions->count() }} {{ Illuminate\Support\Str::plural('attempt', $submissions->count()) }}</span>
                </header>

                @foreach($submissions as $submission)
                    <article class="activity-row" id="submission-{{ $submission->id }}">
                        <header class="activity-attempt-heading">
                            <strong>Attempt {{ $submission->attempt_number }}</strong>
                            <span class="activity-status-pill is-{{ Illuminate\Support\Str::slug($submission->status) }}">
                                {{ Illuminate\Support\Str::headline($submission->status) }}
                            </span>
                            <time>{{ ($submission->submitted_at ?? $submission->created_at)?->format('M j, Y g:i A') }}</time>
                        </header>

                        @if($submission->score !== null)
                            <div class="activity-score">
                                <span>Score</span>
                                <strong>{{ $submission->score }} <span>/ {{ $activity->max_points ?? '—' }}</span></strong>
                            </div>
                        @endif

                        @if($submission->feedback)
                            <div class="activity-review-feedback">
                                <strong>Instructor feedback</strong>
                                <p>{{ $submission->feedback }}</p>
                            </div>
                        @endif

                        @if($submission->submission_path)
                            <a class="activity-file-link" href="{{ route('lesson-activities.download', $submission->id) }}">Download your submitted file</a>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif
    </section>
</x-layout>
