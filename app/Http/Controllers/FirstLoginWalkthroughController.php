<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FirstLoginWalkthroughController extends Controller
{
    public function complete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:start,dismiss'],
        ]);
        $user = $request->user();
        $user->forceFill(['walkthrough_completed_at' => now()])->save();

        if ($validated['action'] === 'dismiss') {
            return back();
        }

        return match ($user->roleName()) {
            'student' => redirect()->route('courses.browse', ['guide' => 1]),
            'faculty' => redirect()->route('faculty.create'),
            default => redirect()->route(
                $user->canAccessAdminPage('courses') ? 'admin.courses' : 'admin.dashboard'
            ),
        };
    }
}
