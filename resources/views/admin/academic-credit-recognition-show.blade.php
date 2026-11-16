<x-admin.layout title="Review Recognition Request | UPSKILL" page="admin-academic-credit-recognition" :user="$user" :use-auth-user="false">
    <div class="layout admin-layout">
        <main class="main recognition-main">
            <div class="recognition-content">
                <p><a href="{{ route('admin.academic-credit-recognition') }}">← All requests</a></p>
                @php
                    $isInternalRequest = in_array($recognition->status, ['submitted', 'under_review', 'needs_information', 'report_generated'], true);
                    $savedFramework = data_get($recognition->framework_snapshot, 'framework', []);
                    $savedStudent = data_get($recognition->framework_snapshot, 'student', []);
                    $frameworkName = data_get($savedFramework, 'name') ?: ($recognition->framework?->name ?? 'Framework unavailable');
                @endphp
                <header class="recognition-head">
                    <h1>Recognition request #{{ $recognition->id }}</h1>
                    <p>Internal UPSKILL review only. No credit, equivalency, or external institutional decision is granted or recorded here.</p>
                </header>

                <x-flash-toast :message="session('success')" />
                @if($errors->any())<div class="notice err" role="alert">{{ $errors->first() }}</div>@endif

                @unless($isInternalRequest)
                    <div class="recognition-notice">
                        <strong>Historical record — read-only</strong>
                        <p>The original status is preserved as <code>{{ $recognition->status }}</code>. This legacy status is not part of the current internal UPSKILL review workflow.</p>
                    </div>
                @endunless

                <section class="recognition-card">
                    <h2>Student and framework</h2>
                    <div class="recognition-grid">
                        <div class="meta"><span>Student</span><strong>{{ data_get($savedStudent, 'name') ?: ($recognition->user?->name ?? 'Account unavailable') }}</strong></div>
                        <div class="meta"><span>Student ID</span><strong>{{ data_get($savedStudent, 'student_id') ?: data_get($savedStudent, 'user_code') ?: ($recognition->user?->student_id ?: $recognition->user?->user_code ?: 'Not recorded') }}</strong></div>
                        <div class="meta"><span>Email</span><strong>{{ data_get($savedStudent, 'email') ?: ($recognition->user?->email ?? 'Not recorded') }}</strong></div>
                        <div class="meta"><span>Request status</span><strong>{{ $isInternalRequest ? Illuminate\Support\Str::headline($recognition->status) : 'Historical record' }}</strong></div>
                        <div class="meta"><span>Framework</span><strong>{{ $frameworkName }}</strong></div>
                        <div class="meta"><span>Framework version</span><strong>{{ data_get($savedFramework, 'version_number') ?: ($recognition->framework?->version_number ?? 'Not recorded') }}</strong></div>
                        <div class="meta"><span>Submitted</span><strong>{{ $recognition->created_at?->format('M j, Y g:i A') ?? 'Not recorded' }}</strong></div>
                        <div class="meta"><span>Approval reference</span><strong>{{ data_get($savedFramework, 'approval_reference') ?: 'Not recorded' }}</strong></div>
                        <div class="meta"><span>Approval / effective dates</span><strong>{{ data_get($savedFramework, 'approval_date') ?: 'Not recorded' }} / {{ data_get($savedFramework, 'effective_date') ?: 'Not recorded' }}</strong></div>
                        <div class="meta"><span>Intended recognition</span><strong>{{ data_get($savedFramework, 'target_recognition') ?: 'Not recorded' }}</strong></div>
                    </div>
                    @if($recognition->notes)<p><strong>Student note:</strong> {{ $recognition->notes }}</p>@endif
                    @if($recognition->student_response)<p><strong>Student's additional information:</strong> {{ $recognition->student_response }}</p>@endif
                    @if($recognition->review_notes)<p><strong>Reviewer note:</strong> {{ $recognition->review_notes }}</p>@endif
                </section>

                @if($isInternalRequest)
                    @if(in_array($recognition->status, ['submitted', 'under_review', 'needs_information'], true))
                        <section class="recognition-card">
                            <h2>Internal review</h2>
                            <form method="POST" action="{{ route('admin.academic-credit-recognition.status', $recognition->id) }}" class="recognition-review-form">
                                @csrf
                                <label for="review-status">Request status</label>
                                <select id="review-status" name="status" required>
                                    <option value="under_review" @selected($recognition->status === 'under_review')>Under Review</option>
                                    <option value="needs_information" @selected($recognition->status === 'needs_information')>Needs Information</option>
                                </select>
                                <label for="review-notes">Internal review note</label>
                                <textarea id="review-notes" name="review_notes" rows="3" maxlength="4000">{{ old('review_notes', $recognition->review_notes) }}</textarea>
                                <button type="submit">Save internal review status</button>
                            </form>
                        </section>
                    @endif
                    @if(in_array($recognition->status, ['under_review', 'report_generated'], true))
                        <div class="recognition-report-action">
                            @if($recognition->report_generated_at)
                                <span>Last generated {{ $recognition->report_generated_at->format('M j, Y g:i A') }}</span>
                            @endif
                            <a class="recognition-action" href="{{ route('admin.credit-evidence', $recognition->id) }}">Download evidence report PDF</a>
                        </div>
                    @endif
                @endif

                @if($evidence)
                    <section class="recognition-card">
                        <h2>Required course completion and learning outcomes</h2>
                        <p class="recognition-framework-description">{{ $evidence['framework']->description }}</p>
                        @if(! empty($evidence['framework']->cumulative_outcomes))
                            <div class="recognition-framework-outcomes">
                                <h3>Cumulative framework outcomes</h3>
                                <ul>@foreach($evidence['framework']->cumulative_outcomes as $outcome)<li>{{ strip_tags((string) $outcome) }}</li>@endforeach</ul>
                            </div>
                        @endif
                        @foreach($evidence['requirements'] as $item)
                            <article class="recognition-course">
                                <header>
                                    <div>
                                        <h3>{{ $item['course']->title ?? 'Removed course' }}</h3>
                                        <p>{{ $item['requirement']->is_required ? 'Required microcredential' : 'Optional legacy requirement' }} · PQF {{ $item['course']->pqf_level ?? 'Not specified' }} · {{ $item['course']->learning_hours ?? 'Not specified' }} learning hours</p>
                                    </div>
                                    @if($item['course']->id)
                                        <a href="{{ route('admin.courses.show', $item['course']->id) }}">Open course record</a>
                                    @endif
                                </header>
                                <p><strong>Completion:</strong> {{ $item['is_completed'] ? 'Officially completed' : 'Not completed' }} · {{ $item['completed_at']?->format('M j, Y g:i A') ?? 'Date not recorded' }}</p>
                                @if($item['outcomes']->isNotEmpty())
                                    <h4>Learning outcomes</h4>
                                    <ul>@foreach($item['outcomes'] as $outcome)<li>{{ strip_tags((string) $outcome->description) }}</li>@endforeach</ul>
                                @endif
                                @if($item['modules']->isNotEmpty())
                                    <h4>Module and lesson activity</h4>
                                    <div class="recognition-evidence-table">
                                        <table><thead><tr><th>Module</th><th>Lesson</th><th>Completion</th></tr></thead><tbody>
                                            @foreach($item['modules'] as $module)
                                                @foreach($module['lessons'] as $lesson)
                                                    <tr><td>{{ $module['title'] }}</td><td>{{ $lesson['title'] }}</td><td>{{ $lesson['completed_at']?->format('M j, Y g:i A') ?? 'Not completed' }}</td></tr>
                                                @endforeach
                                            @endforeach
                                        </tbody></table>
                                    </div>
                                @endif
                                @if($item['quiz_attempts']->isNotEmpty())
                                    <h4>Quiz attempts</h4>
                                    <div class="recognition-evidence-table">
                                        <table><thead><tr><th>Quiz</th><th>Score</th><th>Result</th><th>Submitted</th></tr></thead><tbody>
                                            @foreach($item['quiz_attempts'] as $attempt)
                                                <tr><td>{{ $attempt->quiz?->title ?? 'Quiz' }}</td><td>{{ $attempt->score ?? '—' }}</td><td>{{ $attempt->passed === null ? 'Not recorded' : ($attempt->passed ? 'Passed' : 'Not passed') }}</td><td>{{ $attempt->submitted_at?->format('M j, Y g:i A') ?? '—' }}</td></tr>
                                            @endforeach
                                        </tbody></table>
                                    </div>
                                @endif
                                @if($item['activity_submissions']->isNotEmpty())
                                    <h4>Activity submissions</h4>
                                    <div class="recognition-evidence-table">
                                        <table><thead><tr><th>Activity</th><th>Status</th><th>Score</th><th>Submitted / reviewed</th></tr></thead><tbody>
                                            @foreach($item['activity_submissions'] as $submission)
                                                <tr><td>{{ $submission->activity?->title ?? 'Activity' }}</td><td>{{ Illuminate\Support\Str::headline($submission->status) }}</td><td>{{ $submission->score ?? '—' }}</td><td>{{ $submission->submitted_at?->format('M j, Y') ?? '—' }} / {{ $submission->reviewed_at?->format('M j, Y') ?? 'Not reviewed' }}</td></tr>
                                            @endforeach
                                        </tbody></table>
                                    </div>
                                @endif
                                @if($item['assessments']->isNotEmpty())
                                    <h4>Course assessments</h4>
                                    <div class="recognition-evidence-table">
                                        <table><thead><tr><th>Assessment</th><th>Status</th><th>Score</th><th>Reviewed</th></tr></thead><tbody>
                                            @foreach($item['assessments'] as $assessment)
                                                <tr><td>{{ $assessment->title }}</td><td>{{ Illuminate\Support\Str::headline($assessment->status) }}</td><td>{{ $assessment->score ?? '—' }}</td><td>{{ $assessment->reviewed_at?->format('M j, Y') ?? 'Not reviewed' }}</td></tr>
                                            @endforeach
                                        </tbody></table>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </section>
                @endif
            </div>
        </main>
    </div>
</x-admin.layout>
