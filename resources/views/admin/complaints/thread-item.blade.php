<a class="thread {{ $selected ? 'active' : '' }}"
   data-thread-id="{{ $thread->id }}"
   data-activity="{{ $thread->lastActivityAt()?->format('U.u') }}"
   data-subject="{{ $thread->subject }}"
   data-sender="{{ $thread->senderName() }} {{ $thread->senderContact() }}"
   data-status="{{ strtolower($thread->status) }}"
   data-origin="{{ strtolower($thread->senderRoleLabel()) }}"
   @if($selected) aria-current="page" @endif
   href="{{ route('admin.complaints', ['thread' => $thread->id]) }}">
    <div class="thread-top">
        <span class="thread-subject">{{ $thread->subject }}</span>
        @if ($thread->unreadForAdmin())<span class="dot"></span>@endif
    </div>
    <div class="thread-badges">
        <span class="origin {{ strtolower($thread->senderRoleLabel()) }}">
            {{ $thread->senderRoleLabel() }}
        </span>
        <span class="status {{ $thread->status }}">{{ ucfirst($thread->status) }}</span>
        @if ($thread->attachment_url)
            <span class="clip" title="Has an attachment">📎</span>
        @endif
    </div>
    <div class="thread-meta">
        <span class="thread-who">{{ $thread->senderName() }}</span>
        <span class="thread-dot">·</span>
        <span>{{ $thread->lastActivityAt()?->diffForHumans() }}</span>
    </div>
</a>
