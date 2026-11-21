@php
    $walkthroughUser = auth()->user();
    $walkthroughPending = $walkthroughUser && ! $walkthroughUser->walkthrough_completed_at;
    $walkthroughGuide = match ($walkthroughUser?->roleName()) {
        'faculty' => [
            'role' => 'Faculty',
            'steps' => [
                ['target' => 'faculty-welcome', 'title' => 'Your faculty dashboard', 'description' => 'Start here to see your teaching workspace and shortcuts to the tasks you use most.'],
                ['target' => 'faculty-course-summary', 'title' => 'Keep track of your courses', 'description' => 'These summaries show course publication, student activity, and work that needs your attention.'],
                ['target' => 'faculty-my-courses', 'title' => 'Manage your courses', 'description' => 'Your courses and their review status appear here. Open a course to continue building or editing it.'],
                ['target' => 'faculty-create-course', 'title' => 'Create your first course', 'description' => 'Start a course, add modules and learning activities, then submit it for admin review when it is ready.'],
            ],
            'start_label' => 'Create a course',
        ],
        'admin' => [
            'role' => 'Administrator',
            'steps' => [
                ['target' => 'admin-welcome', 'title' => 'Your admin workspace', 'description' => 'This dashboard is your starting point for platform activity and administrative tasks.'],
                ['target' => 'admin-summary', 'fallback' => 'admin-welcome', 'title' => 'Scan the key numbers', 'description' => 'The summary cards give you a quick view of learners, courses, pending reviews, and credentials available to your account.'],
                ['target' => 'admin-attention', 'fallback' => 'admin-welcome', 'title' => 'See what needs action', 'description' => 'Use Required Attention to jump to pending course submissions or completion verifications.'],
                ['target' => 'admin-quick-access', 'fallback' => 'admin-welcome', 'compact' => true, 'title' => 'Jump to common tasks', 'description' => 'Quick access cards open common admin areas for course reviews, user management, and reports.'],
            ],
            'start_label' => $walkthroughUser?->canAccessAdminPage('courses') ? 'Open Course Approvals' : 'Go to dashboard',
        ],
        default => [
            'role' => 'Student',
            'steps' => [
                ['target' => 'student-welcome', 'title' => 'Your learning home', 'description' => 'Your dashboard brings together your learning activity, progress, and quick ways to get started.'],
                ['target' => 'student-learning-summary', 'title' => 'Track your progress', 'description' => 'These summary cards show your active courses, completed credentials, badges, and certificates.'],
                ['target' => 'student-course-progress', 'title' => 'Continue where you left off', 'description' => 'Enrolled courses and their progress appear here. Open a course to continue learning.'],
                ['target' => 'student-browse-courses', 'title' => 'Find your next course', 'description' => 'Browse the catalog, open a course to review its details, and use the Browse guide for help enrolling.'],
            ],
            'start_label' => 'Browse courses',
        ],
    };
@endphp

@if ($walkthroughUser)
    <x-guided-tour
        tour-id="first-login-tour"
        label="{{ $walkthroughGuide['role'] }} guided tour"
        :steps="$walkthroughGuide['steps']"
        :auto-start="$walkthroughPending"
        :form-action="$walkthroughPending ? route('walkthrough.complete') : null"
        :launcher-label="$walkthroughPending ? null : 'Dashboard guide'"
        :finish-label="$walkthroughPending ? $walkthroughGuide['start_label'] : 'Done'"
        :skip-label="$walkthroughPending ? 'Skip tour' : 'Close guide'"
    />
@endif
