<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return view('settings.index', [
            'user' => $user,
            'categories' => $this->categoriesFor($user),
            'preferences' => $user->notificationPreferences(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $categories = $this->categoriesFor($user);
        $categoryKeys = array_keys($categories);
        $rules = [
            'notifications' => ['sometimes', 'array:'.implode(',', $categoryKeys)],
        ];

        foreach ($categoryKeys as $categoryKey) {
            $rules['notifications.'.$categoryKey] = ['sometimes', 'boolean'];
        }

        $validated = $request->validate($rules);
        $submitted = $validated['notifications'] ?? [];
        $preferences = $user->notificationPreferences();

        foreach ($categoryKeys as $categoryKey) {
            $preferences[$categoryKey] = filter_var(
                $submitted[$categoryKey] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        $user->notification_preferences = $preferences;
        $user->save();

        return redirect()
            ->route('settings.index')
            ->with('success', 'Notification settings saved.');
    }

    public function requestAccountDeletion(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isStudent(), 403);

        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        if ($user->account_deletion_requested_at || ! $user->is_active) {
            return back()->withErrors([
                'account' => 'This account already has a deletion request or is inactive.',
            ]);
        }

        $user->forceFill([
            'account_deletion_requested_at' => now(),
            'is_active' => false,
        ])->save();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'status',
            'Your account is deactivated. Log in within 30 days to cancel deletion and reactivate it.'
        );
    }

    /**
     * @return array<string, array{title: string, description: string}>
     */
    private function categoriesFor(User $user): array
    {
        return match ($user->roleName()) {
            'faculty' => [
                'announcements' => [
                    'title' => 'Announcements',
                    'description' => 'Announcements relevant to you and your courses.',
                ],
                'courses' => [
                    'title' => 'Course updates',
                    'description' => 'Course approval and moderation updates.',
                ],
                'progress' => [
                    'title' => 'Learner progress',
                    'description' => 'New enrollments and completion requests that need review.',
                ],
                'assessments' => [
                    'title' => 'Assessment activity',
                    'description' => 'Assignment submissions awaiting your review.',
                ],
            ],
            'admin' => [
                'announcements' => [
                    'title' => 'Announcements',
                    'description' => 'Announcements published for your role.',
                ],
                'courses' => [
                    'title' => 'Course reviews',
                    'description' => 'Courses submitted for approval and course status updates.',
                ],
                'progress' => [
                    'title' => 'Completion verification',
                    'description' => 'Learner completions that need institutional review.',
                ],
            ],
            default => [
                'announcements' => [
                    'title' => 'Announcements',
                    'description' => 'Announcements published for you and your courses.',
                ],
                'courses' => [
                    'title' => 'Course updates',
                    'description' => 'New courses and enrollment availability.',
                ],
                'progress' => [
                    'title' => 'Learning progress',
                    'description' => 'Enrollment, verification, and course completion updates.',
                ],
                'assessments' => [
                    'title' => 'Assessment updates',
                    'description' => 'Quiz attempt grants and other assessment activity.',
                ],
                'achievements' => [
                    'title' => 'Badges and certificates',
                    'description' => 'Credential awards and updates to your achievements.',
                ],
            ],
        };
    }
}
