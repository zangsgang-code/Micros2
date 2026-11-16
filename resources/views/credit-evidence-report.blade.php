<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>UPSKILL Evidence Report</title>
    <style>
        @page { margin: 18mm 15mm; }
        body { color: #17233c; font: 10px/1.5 Arial, sans-serif; }
        h1, h2, h3 { color: #14236b; }
        h1 { margin: 0; font-size: 22px; }
        h2 { margin: 22px 0 8px; font-size: 15px; }
        h3 { margin: 14px 0 6px; font-size: 12px; }
        p { margin: 4px 0 8px; }
        .eyebrow, .muted, .label { color: #667085; }
        .eyebrow { font-size: 9px; font-weight: bold; letter-spacing: .08em; }
        .muted, .label { font-size: 9px; }
        .summary { width: 100%; margin: 14px 0; border-collapse: separate; border-spacing: 6px; }
        .summary td { width: 33.33%; padding: 9px; vertical-align: top; background: #f5f7fb; }
        .summary strong { display: block; color: #14236b; font-size: 11px; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { width: 50%; padding: 4px 6px; border-bottom: 1px solid #e7ebf2; vertical-align: top; }
        .course { margin-top: 12px; padding: 10px; border: 1px solid #dfe5ef; page-break-inside: avoid; }
        .course table, .records { width: 100%; border-collapse: collapse; margin: 6px 0; }
        .course th, .course td, .records th, .records td { padding: 5px; border: 1px solid #dfe5ef; text-align: left; vertical-align: top; }
        .course th, .records th { background: #f5f7fb; }
        .outcomes { margin: 4px 0 8px; padding-left: 18px; }
        .notice { margin-top: 18px; padding: 10px; border-left: 4px solid #dba617; background: #fff7dc; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <p class="eyebrow">UPSKILL · MICRO-CREDENTIAL EVIDENCE</p>
    <h1>Academic Credit Recognition Evidence</h1>
    <p class="muted">Generated {{ $generated_at->format('M j, Y g:i A') }} · Internal request #{{ $recognition->id }} · Submitted {{ $request_submitted_at?->format('M j, Y g:i A') ?? 'date unavailable' }}</p>

    <table class="summary">
        <tr>
            <td><span class="label">Student</span><strong>{{ $student->name }}</strong>{{ $student->student_id ?: $student->user_code ?: $student->email }}<br>{{ $student->email }}</td>
            <td><span class="label">Framework</span><strong>{{ $framework->name }} · Version {{ $framework->version_number }}</strong>{{ $framework->pqf_level ? 'PQF Level '.$framework->pqf_level : 'PQF level not specified' }}<br>Intended recognition: {{ $framework->target_recognition ?: 'Not specified' }}</td>
            <td><span class="label">Platform completion</span><strong>{{ Illuminate\Support\Str::headline($progress->status ?? 'requirements_met') }}</strong>{{ $completed_required_courses }} of {{ $total_required_courses }} required microcredentials completed</td>
        </tr>
    </table>

    <h2>Framework approval and recognition details</h2>
    <table class="meta">
        <tr><td><span class="label">Approving academic unit</span><br>{{ $framework->approving_academic_unit ?: 'Not recorded' }}</td><td><span class="label">Approval reference</span><br>{{ $framework->approval_reference ?: 'Not recorded' }}</td></tr>
        <tr><td><span class="label">Approval date</span><br>{{ $framework->approval_date ?: 'Not recorded' }}</td><td><span class="label">Effective period</span><br>{{ $framework->effective_date ?: 'Not recorded' }} – {{ $framework->expiration_date ?: 'No expiration date recorded' }}</td></tr>
        <tr><td><span class="label">Recognition target type</span><br>{{ $framework->recognition_target_type ? Illuminate\Support\Str::headline($framework->recognition_target_type) : 'Not specified' }}</td><td><span class="label">Equivalent course / units</span><br>{{ $framework->equivalent_course ?: 'Not specified' }}{{ $framework->equivalent_units !== null ? ' · '.$framework->equivalent_units.' units' : '' }}</td></tr>
        <tr><td colspan="2"><span class="label">Framework description</span><br>{{ $framework->description ?: 'Not recorded' }}</td></tr>
        <tr><td colspan="2"><span class="label">Recognition conditions</span><br>{{ $framework->credit_recognition_conditions ?: 'No additional conditions recorded' }}</td></tr>
    </table>

    @if(! empty($framework->cumulative_outcomes))
        <h2>Cumulative framework learning outcomes</h2>
        <ul class="outcomes">
            @foreach($framework->cumulative_outcomes as $outcome)
                <li>{{ strip_tags((string) $outcome) }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Required microcredential completion evidence</h2>
    @foreach($requirements as $item)
        <article class="course">
            <h3>{{ $loop->iteration }}. {{ $item['course']->title ?? 'Removed course' }}</h3>
            <table class="meta">
                <tr>
                    <td><span class="label">Framework requirement</span><br>{{ $item['requirement']->is_required ? 'Required' : 'Optional' }}</td>
                    <td><span class="label">Official completion</span><br>{{ $item['is_completed'] ? 'Completed · '.($item['completed_at']?->format('M j, Y') ?? 'date unavailable') : 'Not completed' }}</td>
                </tr>
                <tr>
                    <td><span class="label">PQF level / learning hours</span><br>{{ $item['course']->pqf_level ?? 'Not specified' }} / {{ $item['course']->learning_hours ?? 'Not specified' }}</td>
                    <td><span class="label">Credential record</span><br>@if($item['certificate'])Certificate {{ $item['certificate']->serial }} ({{ Illuminate\Support\Str::headline($item['certificate']->status ?? 'issued') }})@elseif($item['badge']){{ $item['badge']->badge?->name ?? 'Badge' }} · {{ $item['badge']->earned_at?->format('M j, Y') }}@else No issued certificate or badge found @endif</td>
                </tr>
            </table>

            @if($item['outcomes']->isNotEmpty())
                <p><strong>Learning outcomes</strong></p>
                <ul class="outcomes">
                    @foreach($item['outcomes'] as $outcome)
                        <li>{{ strip_tags((string) $outcome->description) }}</li>
                    @endforeach
                </ul>
            @endif

            @if($item['modules']->isNotEmpty())
                <p><strong>Learning activity</strong></p>
                <table class="records">
                    <thead><tr><th>Module</th><th>Lesson</th><th>Completion</th></tr></thead>
                    <tbody>
                    @foreach($item['modules'] as $module)
                        @foreach($module['lessons'] as $lesson)
                            <tr>
                                <td>{{ $module['title'] }}</td>
                                <td>{{ $lesson['title'] }}</td>
                                <td>{{ $lesson['completed_at']?->format('M j, Y g:i A') ?? 'Not recorded as complete' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            @endif

            @if($item['quiz_attempts']->isNotEmpty())
                <p><strong>Quiz and assessment attempts</strong></p>
                <table class="records">
                    <thead><tr><th>Quiz</th><th>Score</th><th>Result</th><th>Submitted</th></tr></thead>
                    <tbody>
                    @foreach($item['quiz_attempts'] as $attempt)
                        <tr>
                            <td>{{ $attempt->quiz?->title ?? 'Quiz' }}</td>
                            <td>{{ $attempt->score ?? 'Not recorded' }}</td>
                            <td>{{ $attempt->passed === null ? 'Not recorded' : ($attempt->passed ? 'Passed' : 'Not passed') }}</td>
                            <td>{{ $attempt->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

            @if($item['activity_submissions']->isNotEmpty())
                <p><strong>Activity submissions</strong></p>
                <table class="records">
                    <thead><tr><th>Activity</th><th>Status</th><th>Score</th><th>Submitted / reviewed</th></tr></thead>
                    <tbody>
                    @foreach($item['activity_submissions'] as $submission)
                        <tr>
                            <td>{{ $submission->activity?->title ?? 'Activity' }}</td>
                            <td>{{ Illuminate\Support\Str::headline($submission->status) }}</td>
                            <td>{{ $submission->score ?? 'Not recorded' }}</td>
                            <td>{{ $submission->submitted_at?->format('M j, Y') ?? '—' }} / {{ $submission->reviewed_at?->format('M j, Y') ?? 'Not reviewed' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

            @if($item['assessments']->isNotEmpty())
                <p><strong>Recorded course assessments</strong></p>
                <table class="records">
                    <thead><tr><th>Assessment</th><th>Status</th><th>Score</th><th>Reviewed</th></tr></thead>
                    <tbody>
                    @foreach($item['assessments'] as $assessment)
                        <tr>
                            <td>{{ $assessment->title }}</td>
                            <td>{{ Illuminate\Support\Str::headline($assessment->status) }}</td>
                            <td>{{ $assessment->score ?? 'Not recorded' }}</td>
                            <td>{{ $assessment->reviewed_at?->format('M j, Y') ?? 'Not reviewed' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </article>
    @endforeach

    <h2>Evidence totals</h2>
    <table class="meta">
        <tr><td>Total required microcredentials</td><td>{{ $total_required_courses }}</td></tr>
        <tr><td>Officially completed microcredentials</td><td>{{ $completed_required_courses }}</td></tr>
        <tr><td>Recorded learning hours</td><td>{{ $total_learning_hours ?: 'Not recorded' }}</td></tr>
        <tr><td>Quiz attempts / activity submissions / course assessments</td><td>{{ $total_quiz_attempts }} / {{ $total_activity_submissions }} / {{ $total_assessments }}</td></tr>
    </table>

    <div class="notice"><strong>Supporting documentation only.</strong> This report summarizes UPSKILL records for institutional review. It does not award academic credit, determine equivalency, grant recognition, or record an external institutional decision.</div>
</body>
</html>
