{{--
    resources/views/admin/Complaints.blade.php
    Admin › Inbox — Help Center messages from students, faculty, and visitors.
    Expects: $threads, $selected (Complaint|null), $unreadCount.
--}}
<x-admin.layout title="Inbox | UPSKILL Admin" page="admin-complaints">
    <x-slot:head>
        <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            {{-- Browser tab icon (favicon) --}}
            <link rel="icon" type="image/png" href="{{ asset('Images/PSU-Logo.png') }}">
            <link rel="apple-touch-icon" href="{{ asset('Images/PSU-Logo.png') }}">

            @vite(['resources/js/app.js', 'resources/css/inbox-reply.css'])
    </x-slot:head>
<div class="wrap inbox-page">
    <h1 class="page-heading">Inbox</h1>
    <p class="page-sub">Message threads from students, faculty, and website visitors. Reply here to continue the conversation.</p>

    <x-flash-toast :message="session('success')" />

    {{-- Delivery failed: the reply is safely stored, but nothing was sent. --}}
    @error('mail')
        <div class="alert alert-warn">{{ $message }}</div>
    @enderror

    <div class="grid">
        {{-- ── Thread list ── --}}
        <nav class="panel" aria-label="Message threads"
             data-inbox-thread-list
             data-poll-url="{{ route('admin.complaints.updates') }}"
             data-updates-since="{{ now()->toIso8601String() }}">
            <div class="panel-hd">
                <span>Messages</span>
                <span class="count-pill" data-inbox-unread-count data-unread-suffix=" new"
                      @if($unreadCount === 0) style="display:none" @endif>{{ $unreadCount }} new</span>
            </div>
            <div class="thread-filters" role="search" aria-label="Filter message threads">
                <label for="thread-search" class="sr-only">Search messages</label>
                <input class="thread-search" id="thread-search" type="search" placeholder="Search subject or sender…" autocomplete="off">
                <div class="thread-filter-row">
                    <label for="thread-status" class="sr-only">Filter by status</label>
                    <select class="thread-filter-select" id="thread-status">
                        <option value="">All statuses</option>
                        <option value="open">Open</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <label for="thread-origin" class="sr-only">Filter by sender type</label>
                    <select class="thread-filter-select" id="thread-origin">
                        <option value="">All senders</option>
                        <option value="student">Students</option>
                        <option value="faculty">Faculty</option>
                        <option value="visitor">Visitors</option>
                    </select>
                </div>
                <div class="thread-filter-meta">
                    <span id="thread-result-count" aria-live="polite"></span>
                    <button class="thread-filter-clear" id="thread-filter-clear" type="button">Clear filters</button>
                </div>
            </div>

            <div class="thread-list-scroll" data-thread-scroll>
                @forelse ($threads as $t)
                    @include('admin.complaints.thread-item', ['thread' => $t, 'selected' => $selected && $selected->id === $t->id])
                @empty
                    <div class="empty" data-empty-threads>No messages yet.</div>
                @endforelse
                <div class="thread-filter-empty" id="thread-filter-empty">No messages match these filters.</div>
            </div>
            <div class="thread-pagination" data-thread-pagination role="group" aria-label="Message thread pages" hidden>
                <button class="thread-page-button" type="button" data-thread-page-prev disabled>Previous</button>
                <span data-thread-page-status aria-live="polite"></span>
                <button class="thread-page-button" type="button" data-thread-page-next disabled>Next</button>
            </div>
        </nav>

        {{-- ── Selected thread ── --}}
        <div class="panel">
            @if ($selected)
                <div class="detail-hd">
                    <div class="detail-hd-top">
                        <h2>{{ $selected->subject }}</h2>
                        <form class="resolve-form" method="POST" action="{{ route('admin.complaints.resolve', $selected->id) }}">
                            @csrf
                            <input type="hidden" name="threadPage" value="{{ request()->query('threadPage', 1) }}">
                            <button type="submit" class="btn-ghost">
                                {{ $selected->status === 'resolved' ? 'Reopen conversation' : 'Mark as resolved' }}
                            </button>
                        </form>
                    </div>
                    <div class="who">
                        <span class="origin {{ strtolower($selected->senderRoleLabel()) }}">
                            {{ $selected->senderRoleLabel() }}
                        </span>
                        From {{ $selected->senderName() }}
                        @if ($selected->isFromVisitor())
                            @if ($selected->guest_email)
                                (<a href="mailto:{{ $selected->guest_email }}" class="adm-inline-admin-complaints-1"
                                   >{{ $selected->guest_email }}</a>)
                            @endif
                        @else
                            ({{ $selected->user->user_code ?? '—' }})
                        @endif
                        · {{ $selected->created_at?->format('M j, Y g:i A') }}
                    </div>
                </div>

                <div class="msgs" id="thread-messages" data-inbox-message-stream data-own-message-class="from-admin"
                     data-thread-id="{{ $selected->id }}">
                    {{-- Opening message --}}
                    <div class="msg from-student">
                        <div>
                            <div class="msg-who">{{ $selected->senderName() }}</div>
                            <div class="bubble">{{ $selected->message }}</div>

                            @if ($selected->attachment_url)
                                <div class="attach">
                                    @if ($selected->attachmentIsImage())
                                        {{-- Click to open full size --}}
                                        <img src="{{ route('complaints.attachment', $selected->id) }}"
                                             alt="{{ $selected->attachment_name }}"
                                             onclick="openLightbox(this.src)">
                                    @else
                                        <a class="attach-file" target="_blank"
                                           href="{{ route('complaints.attachment', $selected->id) }}">
                                            📎 {{ $selected->attachment_name ?? 'Attachment' }}
                                        </a>
                                    @endif
                                    <div class="attach-name">{{ $selected->attachment_name }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @foreach ($selected->replies as $reply)
                        <div class="msg {{ $reply->is_admin ? 'from-admin' : 'from-student' }}" data-message-id="{{ $reply->id }}">
                            <div>
                                <div class="msg-who" style="{{ $reply->is_admin ? 'text-align:right;' : '' }}">
                                    {{ $reply->is_admin ? 'Administrator' : ($reply->author->name ?? 'Student') }}
                                    · {{ $reply->created_at?->diffForHumans() }}
                                </div>
                                <div class="bubble">@if ($selected->isFromVisitor() && $reply->is_admin){!! \App\Support\RichTextSanitizer::sanitize((string) $reply->body) !!}@else{{ $reply->body }}@endif</div>
                                @if ($reply->attachment_url)
                                    <div class="attach">
                                        @if ($reply->attachmentIsImage())
                                            <img src="{{ route('complaint-replies.attachment', $reply->id) }}"
                                                 alt="{{ $reply->attachment_name }}"
                                                 onclick="openLightbox(this.src)">
                                        @else
                                            <a class="attach-file" href="{{ route('complaint-replies.attachment', $reply->id) }}" target="_blank" rel="noopener">
                                                📎 {{ $reply->attachment_name ?? 'Attachment' }}
                                            </a>
                                        @endif
                                        <div class="attach-name">{{ $reply->attachment_name }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="reply-box">
                    <form class="inbox-reply-compose {{ $selected->isFromVisitor() ? 'visitor-reply-compose' : 'reply-field-compose' }}" method="POST" enctype="multipart/form-data" action="{{ route('admin.complaints.reply', $selected->id) }}">
                        @csrf
                        <input type="hidden" name="threadPage" value="{{ request()->query('threadPage', 1) }}">
                        @if ($selected->isFromVisitor())
                            <x-rich-text-editor
                                id="admin-reply"
                                name="body"
                                :value="old('body')"
                                preset="admin-reply"
                                placeholder="Write your reply — it will be emailed to them…"
                            />
                            <div class="reply-actions">
                                <button type="submit" class="btn-navy">Send Reply</button>
                            </div>
                        @else
                            <label for="admin-reply-attachment" class="reply-attachment-picker" title="Attach an image" aria-label="Attach an image" role="button" tabindex="0">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.4 11.1 12.8 19.7a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg>
                            </label>
                            <input class="reply-attachment-input" type="file" id="admin-reply-attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                            <textarea name="body" rows="1" placeholder="Write your reply to the {{ strtolower($selected->senderRoleLabel()) }}…" required>{{ old('body') }}</textarea>
                            <div class="reply-actions">
                                <button type="submit" class="btn-navy">Send Reply</button>
                            </div>
                            <p class="reply-attachment-name" id="admin-reply-attachment-name">Images only, up to 10 MB.</p>
                            @error('attachment')
                                <div class="reply-attachment-error">{{ $message }}</div>
                            @enderror
                            <div class="reply-attachment-preview" id="admin-reply-attachment-preview" hidden>
                                <img alt="Selected image attachment">
                            </div>
                        @endif
                        @if ($selected->isFromVisitor() && $selected->guest_email)
                            <div class="mail-note">
                                ✉ This reply will be emailed to {{ $selected->guest_email }}.
                            </div>
                        @endif
                    </form>
                </div>
            @else
                <div class="empty">Select a message on the left to read and reply.</div>
            @endif
        </div>
    </div>
</div>

{{-- Full-size attachment viewer --}}
<div id="lightbox" onclick="closeLightbox(event)">
    <button type="button" class="lb-close" onclick="closeLightbox(event, true)" aria-label="Close">&times;</button>
    <img id="lightbox-img" src="" alt="Attachment">
</div>

<script>
    (function () {
        var search = document.getElementById('thread-search');
        var status = document.getElementById('thread-status');
        var origin = document.getElementById('thread-origin');
        var clear = document.getElementById('thread-filter-clear');
        var count = document.getElementById('thread-result-count');
        var noResults = document.getElementById('thread-filter-empty');
        var threads = Array.prototype.slice.call(document.querySelectorAll('.thread[data-status]'));
        var threadScroll = document.querySelector('[data-thread-scroll]');
        var pagination = document.querySelector('[data-thread-pagination]');
        var pageStatus = document.querySelector('[data-thread-page-status]');
        var previousPage = document.querySelector('[data-thread-page-prev]');
        var nextPage = document.querySelector('[data-thread-page-next]');
        var requestedPage = Number(new URLSearchParams(window.location.search).get('threadPage'));
        var currentPage = Number.isSafeInteger(requestedPage) && requestedPage > 0 ? requestedPage : 1;
        var threadsPerPage = 10;

        function applyThreadPagination() {
            var matchingThreads = threads.filter(function (thread) {
                return !thread.hidden;
            });
            var pageCount = Math.max(1, Math.ceil(matchingThreads.length / threadsPerPage));
            currentPage = Math.min(currentPage, pageCount);

            var firstIndex = (currentPage - 1) * threadsPerPage;
            var lastIndex = Math.min(firstIndex + threadsPerPage, matchingThreads.length);
            matchingThreads.forEach(function (thread, index) {
                thread.classList.toggle('thread-page-hidden', index < firstIndex || index >= lastIndex);
            });

            pagination.hidden = matchingThreads.length <= threadsPerPage;
            pageStatus.textContent = matchingThreads.length
                ? 'Showing ' + (firstIndex + 1) + '-' + lastIndex + ' of ' + matchingThreads.length
                : 'Showing 0 threads';
            previousPage.disabled = currentPage === 1;
            nextPage.disabled = currentPage === pageCount;
            threads.forEach(function (thread) {
                var url = new URL(thread.href);
                if (currentPage > 1) {
                    url.searchParams.set('threadPage', String(currentPage));
                } else {
                    url.searchParams.delete('threadPage');
                }
                thread.href = url.toString();
            });
        }

        function applyThreadFilters(keepCurrentPage) {
            if (keepCurrentPage !== true) currentPage = 1;
            var query = search.value.trim().toLocaleLowerCase();
            var visibleCount = 0;

            threads.forEach(function (thread) {
                var matchesQuery = (thread.dataset.subject + ' ' + thread.dataset.sender)
                    .toLocaleLowerCase().indexOf(query) !== -1;
                var matchesStatus = !status.value || thread.dataset.status === status.value;
                var matchesOrigin = !origin.value || thread.dataset.origin === origin.value;
                var isVisible = matchesQuery && matchesStatus && matchesOrigin;

                thread.hidden = !isVisible;
                if (isVisible) visibleCount += 1;
            });

            count.textContent = 'Showing ' + visibleCount + ' of ' + threads.length;
            noResults.classList.toggle('is-visible', threads.length > 0 && visibleCount === 0);
            applyThreadPagination();
        }

        search.addEventListener('input', applyThreadFilters);
        status.addEventListener('change', applyThreadFilters);
        origin.addEventListener('change', applyThreadFilters);
        previousPage.addEventListener('click', function () {
            if (currentPage <= 1) return;
            currentPage -= 1;
            applyThreadPagination();
            threadScroll.scrollTop = 0;
        });
        nextPage.addEventListener('click', function () {
            var pageCount = Math.max(1, Math.ceil(threads.filter(function (thread) {
                return !thread.hidden;
            }).length / threadsPerPage));
            if (currentPage >= pageCount) return;
            currentPage += 1;
            applyThreadPagination();
            threadScroll.scrollTop = 0;
        });
        clear.addEventListener('click', function () {
            search.value = '';
            status.value = '';
            origin.value = '';
            applyThreadFilters();
            search.focus();
        });
        window.addEventListener('admin-inbox:threads-updated', function () {
            threads = Array.prototype.slice.call(document.querySelectorAll('.thread[data-status]'));
            applyThreadFilters(true);
        });

        applyThreadFilters();
    }());

    function openLightbox(src) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    // Clicking the backdrop or the close button dismisses it; clicking the
    // image itself does not.
    function closeLightbox(e, force) {
        if (!force && e && e.target.id === 'lightbox-img') return;
        document.getElementById('lightbox').classList.remove('open');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeLightbox(null, true);
    });
</script>

{{-- Shared Admin sidebar styling (floating card + section pills) --}}
{{-- Shared responsiveness layer (drawer nav + grid stacking) --}}
<script>
    const replyAttachmentInput = document.getElementById('admin-reply-attachment');
    if (replyAttachmentInput) {
        const attachmentPicker = document.querySelector('.reply-attachment-picker');
        attachmentPicker.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                replyAttachmentInput.click();
            }
        });
        replyAttachmentInput.addEventListener('change', function () {
            const name = document.getElementById('admin-reply-attachment-name');
            const preview = document.getElementById('admin-reply-attachment-preview');
            const image = preview.querySelector('img');
            const file = this.files[0];

            name.textContent = file ? file.name : 'Images only, up to 10 MB.';
            preview.hidden = !file || !file.type.startsWith('image/');
            if (file && file.type.startsWith('image/')) {
                image.src = URL.createObjectURL(file);
            }
        });
    }

    const replyText = document.querySelector('.reply-field-compose textarea');
    if (replyText) {
        const resizeReplyText = function () {
            replyText.style.height = '44px';
            const maxHeight = 112;
            const nextHeight = Math.min(replyText.scrollHeight, maxHeight);
            replyText.style.height = `${nextHeight}px`;
            replyText.style.overflowY = replyText.scrollHeight > maxHeight ? 'auto' : 'hidden';
        };

        replyText.addEventListener('input', resizeReplyText);
        resizeReplyText();
    }
</script>
</x-admin.layout>