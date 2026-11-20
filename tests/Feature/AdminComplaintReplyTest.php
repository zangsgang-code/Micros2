<?php

use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('admin inbox uses rich text only for visitor replies', function () {
    $admin = User::factory()->admin()->create();
    $faculty = User::factory()->faculty()->create();
    $facultyThread = Complaint::create([
        'user_id' => $faculty->id,
        'source' => 'faculty',
        'subject' => 'Faculty question',
        'message' => 'Need assistance.',
        'status' => 'open',
    ]);
    $visitorThread = Complaint::create([
        'source' => 'visitor',
        'guest_name' => 'Site visitor',
        'guest_email' => 'visitor@example.test',
        'subject' => 'Visitor question',
        'message' => 'Need information.',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.complaints', ['thread' => $facultyThread->id]))
        ->assertOk()
        ->assertDontSee('data-ckeditor')
        ->assertSee('name="attachment"', false)
        ->assertSee('reply-attachment-picker', false)
        ->assertSee('<textarea name="body" rows="1"', false)
        ->assertSee('detail-hd-top', false)
        ->assertSee('Mark as resolved');

    $this->actingAs($admin)
        ->get(route('admin.complaints', ['thread' => $visitorThread->id]))
        ->assertOk()
        ->assertSee('data-ckeditor')
        ->assertSee('Send Reply')
        ->assertSee('Mark as resolved')
        ->assertDontSee('name="attachment"', false);
});

test('admin can send a plain text faculty reply with an image attachment', function () {
    $admin = User::factory()->admin()->create();
    $faculty = User::factory()->faculty()->create();
    $thread = Complaint::create([
        'user_id' => $faculty->id,
        'source' => 'faculty',
        'subject' => 'Faculty question',
        'message' => 'Need assistance.',
        'status' => 'open',
    ]);
    $image = UploadedFile::fake()->image('screenshot.png');

    $this->actingAs($admin)
        ->post(route('admin.complaints.reply', $thread->id), [
            'body' => 'Please see the screenshot.',
            'attachment' => $image,
        ])
        ->assertRedirect(route('admin.complaints', ['thread' => $thread->id]).'#thread-messages');

    $reply = ComplaintReply::where('complaint_id', $thread->id)->firstOrFail();

    expect($reply->body)->toBe('Please see the screenshot.')
        ->and($reply->attachment_name)->toBe('screenshot.png')
        ->and(is_file(public_path($reply->attachment_url)))->toBeTrue();

    $this->actingAs($faculty)
        ->get(route('complaint-replies.attachment', $reply->id))
        ->assertOk();

    $otherUser = User::factory()->create();
    $this->actingAs($otherUser)
        ->get(route('complaint-replies.attachment', $reply->id))
        ->assertForbidden();

    $this->actingAs($faculty)
        ->get(route('faculty.inbox', ['thread' => $thread->id]))
        ->assertOk()
        ->assertSee(route('complaint-replies.attachment', $reply->id), false);

    unlink(public_path($reply->attachment_url));
});

test('student and faculty inboxes can send replies with image attachments', function () {
    foreach ([
        ['user' => User::factory()->create(), 'source' => 'student', 'route' => 'inbox.index', 'replyRoute' => 'inbox.reply'],
        ['user' => User::factory()->faculty()->create(), 'source' => 'faculty', 'route' => 'faculty.inbox', 'replyRoute' => 'faculty.inbox.reply'],
    ] as $case) {
        $thread = Complaint::create([
            'user_id' => $case['user']->id,
            'source' => $case['source'],
            'subject' => ucfirst($case['source']).' question',
            'message' => 'Need assistance.',
            'status' => 'open',
        ]);

        $this->actingAs($case['user'])
            ->get(route($case['route'], ['thread' => $thread->id]))
            ->assertOk()
            ->assertSee('reply-field-compose', false)
            ->assertSee('reply-attachment-picker', false)
            ->assertSee('name="attachment"', false)
            ->assertSee('rows="1"', false);

        $this->actingAs($case['user'])
            ->post(route($case['replyRoute'], $thread->id), [
                'body' => 'Here is an image.',
                'attachment' => UploadedFile::fake()->image('reply.png'),
            ])
            ->assertRedirect(route($case['route'], ['thread' => $thread->id]).'#thread-messages');

        $reply = ComplaintReply::where('complaint_id', $thread->id)->firstOrFail();

        expect($reply->body)->toBe('Here is an image.')
            ->and($reply->attachment_name)->toBe('reply.png')
            ->and(is_file(public_path($reply->attachment_url)))->toBeTrue();

        $this->actingAs($case['user'])
            ->get(route('complaint-replies.attachment', $reply->id))
            ->assertOk();

        unlink(public_path($reply->attachment_url));
    }
});

test('admin can fetch only replies after the selected thread message id', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $selectedThread = Complaint::create([
        'user_id' => $student->id,
        'source' => 'student',
        'subject' => 'Incremental replies',
        'message' => 'Need help.',
        'status' => 'open',
        'last_reply_at' => now(),
        'admin_read_at' => now()->subMinute(),
    ]);
    $otherThread = Complaint::create([
        'user_id' => User::factory()->create()->id,
        'source' => 'student',
        'subject' => 'Other conversation',
        'message' => 'Another question.',
        'status' => 'open',
        'last_reply_at' => now(),
        'admin_read_at' => now()->subMinute(),
    ]);

    $firstReply = ComplaintReply::create([
        'complaint_id' => $selectedThread->id,
        'user_id' => $admin->id,
        'body' => 'First reply.',
        'is_admin' => true,
    ]);
    ComplaintReply::create([
        'complaint_id' => $otherThread->id,
        'user_id' => $otherThread->user_id,
        'body' => 'Other thread reply.',
        'is_admin' => false,
    ]);
    $secondReply = ComplaintReply::create([
        'complaint_id' => $selectedThread->id,
        'user_id' => $student->id,
        'body' => 'Student follow-up.',
        'is_admin' => false,
    ]);
    $thirdReply = ComplaintReply::create([
        'complaint_id' => $selectedThread->id,
        'user_id' => $admin->id,
        'body' => 'Administrator follow-up.',
        'is_admin' => true,
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.complaints.replies', [
            'id' => $selectedThread->id,
            'sinceId' => $firstReply->id,
        ]))
        ->assertOk()
        ->assertJsonCount(2, 'messages')
        ->assertJsonPath('messages.0.id', $secondReply->id)
        ->assertJsonPath('messages.1.id', $thirdReply->id)
        ->assertJsonPath('unread_count', 1);

    $this->getJson(route('admin.complaints.replies', [
        'id' => $selectedThread->id,
        'sinceId' => $thirdReply->id,
    ]))
        ->assertOk()
        ->assertJsonPath('messages', []);

    $this->actingAs(User::factory()->faculty()->create())
        ->getJson(route('admin.complaints.replies', ['id' => $selectedThread->id]))
        ->assertRedirect(route('faculty.dashboard'));
});

test('admin inbox polling returns new unselected threads and unread state', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $since = now()->subMinute();
    $oldThread = Complaint::create([
        'user_id' => User::factory()->create()->id,
        'source' => 'student',
        'subject' => 'Older thread',
        'message' => 'Already on the list.',
        'status' => 'open',
        'last_reply_at' => now()->subHours(2),
        'admin_read_at' => now(),
    ]);
    Complaint::whereKey($oldThread->id)->update(['updated_at' => now()->subHour()]);

    $newThread = Complaint::create([
        'user_id' => $student->id,
        'source' => 'student',
        'subject' => 'New unviewed thread',
        'message' => 'Please help.',
        'status' => 'open',
        'last_reply_at' => now(),
    ]);
    $reply = ComplaintReply::create([
        'complaint_id' => $newThread->id,
        'user_id' => $student->id,
        'body' => 'A new message.',
        'is_admin' => false,
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.complaints.updates', [
            'sinceUpdatedAt' => $since->toIso8601String(),
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'threads')
        ->assertJsonPath('threads.0.id', $newThread->id)
        ->assertJsonPath('messages', [])
        ->assertJsonPath('unread_count', 1);

    expect($response->json('threads.0.html'))
        ->toContain('New unviewed thread')
        ->toContain('<span class="dot"></span>');

    $this->getJson(route('admin.complaints.updates', [
        'sinceUpdatedAt' => $response->json('checked_at'),
        'threadId' => $newThread->id,
        'sinceId' => 0,
    ]))
        ->assertOk()
        ->assertJsonPath('messages.0.id', $reply->id)
        ->assertJsonPath('unread_count', 0);
});

test('admin inbox message list has a scrollable paginated container', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    foreach (range(1, 11) as $threadNumber) {
        Complaint::create([
            'user_id' => $student->id,
            'source' => 'student',
            'subject' => 'Thread '.$threadNumber,
            'message' => 'Need assistance.',
            'status' => 'open',
            'last_reply_at' => now()->subSeconds($threadNumber),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.complaints'))
        ->assertOk()
        ->assertSee('data-thread-scroll', false)
        ->assertSee('aria-label="Message thread pages"', false)
        ->assertSee('data-thread-page-prev', false)
        ->assertSee('data-thread-page-next', false);
});

test('student and faculty inboxes poll only their own threads', function () {
    $admin = User::factory()->admin()->create();

    foreach ([
        [
            'user' => User::factory()->create(),
            'source' => 'student',
            'inboxRoute' => 'inbox.index',
            'pollRoute' => 'inbox.replies',
        ],
        [
            'user' => User::factory()->faculty()->create(),
            'source' => 'faculty',
            'inboxRoute' => 'faculty.inbox',
            'pollRoute' => 'faculty.inbox.replies',
        ],
    ] as $case) {
        $user = $case['user'];
        $thread = Complaint::create([
            'user_id' => $user->id,
            'source' => $case['source'],
            'subject' => 'Inbox polling',
            'message' => 'Need assistance.',
            'status' => 'open',
            'last_reply_at' => now(),
            'student_read_at' => now()->subMinute(),
        ]);
        $otherThread = Complaint::create([
            'user_id' => $user->id,
            'source' => $case['source'],
            'subject' => 'Another unread conversation',
            'message' => 'Another question.',
            'status' => 'open',
            'last_reply_at' => now(),
            'student_read_at' => now()->subMinute(),
        ]);
        Complaint::create([
            'user_id' => User::factory()->create()->id,
            'source' => 'student',
            'subject' => 'Private conversation',
            'message' => "Not this user's thread.",
            'status' => 'open',
        ]);

        $reply = ComplaintReply::create([
            'complaint_id' => $thread->id,
            'user_id' => $admin->id,
            'body' => 'Administrator response.',
            'is_admin' => true,
        ]);
        ComplaintReply::create([
            'complaint_id' => $otherThread->id,
            'user_id' => $admin->id,
            'body' => 'Another unread response.',
            'is_admin' => true,
        ]);

        $this->actingAs($user)
            ->get(route($case['inboxRoute'], ['thread' => $thread->id]))
            ->assertOk()
            ->assertSee('data-inbox-message-stream', false)
            ->assertSee('id="thread-messages"', false)
            ->assertSee('data-user-thread-list', false)
            ->assertSee('data-user-thread-scroll', false)
            ->assertSee('data-user-thread-pagination', false)
            ->assertSee(route($case['pollRoute'], $thread->id), false)
            ->assertSee('data-message-id="'.$reply->id.'"', false);

        $this->getJson(route($case['pollRoute'], [
            'id' => $thread->id,
            'sinceId' => 0,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $reply->id)
            ->assertJsonPath('messages.0.author', 'Administrator')
            ->assertJsonPath('unread_count', 1);

        $this->getJson(route($case['pollRoute'], [
            'id' => $thread->id,
            'sinceId' => $reply->id,
        ]))
            ->assertOk()
            ->assertJsonPath('messages', []);

        $otherUserThread = Complaint::where('subject', 'Private conversation')->firstOrFail();
        $this->getJson(route($case['pollRoute'], ['id' => $otherUserThread->id]))
            ->assertNotFound();
    }
});
