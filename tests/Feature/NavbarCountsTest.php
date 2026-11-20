<?php

use App\Models\Complaint;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated topbar counts include only the signed-in users unread notifications and inbox threads', function () {
    $student = User::factory()->create();
    $otherStudent = User::factory()->create();

    Notification::create([
        'user_id' => $student->id,
        'title' => 'New course',
        'message' => 'A course is available.',
        'type' => 'course',
        'is_read' => false,
    ]);
    Notification::create([
        'user_id' => $student->id,
        'title' => 'Read course',
        'message' => 'Already read.',
        'type' => 'course',
        'is_read' => true,
    ]);
    Notification::create([
        'user_id' => $otherStudent->id,
        'title' => 'Other notification',
        'message' => 'Not yours.',
        'type' => 'course',
        'is_read' => false,
    ]);

    Complaint::create([
        'user_id' => $student->id,
        'source' => 'student',
        'subject' => 'Unread thread',
        'message' => 'Please help.',
        'status' => 'open',
        'last_reply_at' => now(),
        'student_read_at' => now()->subMinute(),
    ]);
    Complaint::create([
        'user_id' => $student->id,
        'source' => 'student',
        'subject' => 'Read thread',
        'message' => 'Resolved.',
        'status' => 'open',
        'last_reply_at' => now()->subMinute(),
        'student_read_at' => now(),
    ]);
    Complaint::create([
        'user_id' => $otherStudent->id,
        'source' => 'student',
        'subject' => 'Other thread',
        'message' => 'Not yours.',
        'status' => 'open',
        'last_reply_at' => now(),
        'student_read_at' => now()->subMinute(),
    ]);

    $this->actingAs($student)
        ->getJson(route('notifications.counts'))
        ->assertOk()
        ->assertExactJson([
            'unread_notifications' => 1,
            'unread_inbox' => 1,
        ]);
});

test('admin topbar inbox count includes unread threads and notification count', function () {
    $admin = User::factory()->admin()->create();

    Notification::create([
        'user_id' => $admin->id,
        'title' => 'New review',
        'message' => 'Review required.',
        'type' => 'course',
        'is_read' => false,
    ]);
    Complaint::create([
        'source' => 'visitor',
        'guest_name' => 'Website visitor',
        'subject' => 'Unread visitor thread',
        'message' => 'Please help.',
        'status' => 'open',
        'last_reply_at' => now(),
        'admin_read_at' => now()->subMinute(),
    ]);
    Complaint::create([
        'source' => 'visitor',
        'guest_name' => 'Website visitor',
        'subject' => 'Read visitor thread',
        'message' => 'Already reviewed.',
        'status' => 'open',
        'last_reply_at' => now()->subMinute(),
        'admin_read_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson(route('notifications.counts'))
        ->assertOk()
        ->assertExactJson([
            'unread_notifications' => 1,
            'unread_inbox' => 1,
        ]);

    $this->actingAs($admin)
        ->get(route('admin.complaints'))
        ->assertOk()
        ->assertSee('data-counts-url="'.route('notifications.counts').'"', false)
        ->assertSee('data-topbar-count-target="notifications"', false)
        ->assertSee('data-topbar-count-target="inbox"', false);
});

test('topbar counts require authentication', function () {
    $this->getJson(route('notifications.counts'))
        ->assertRedirect(route('login'));
});
