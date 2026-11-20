
<x-admin.layout title="Upskill – Courses & Badges" page="admin-courses-index">
    <x-slot:head>
        <link rel="icon" type="image/png" href="{{ asset('Images/PSU-Logo.png') }}">
            <link rel="apple-touch-icon" href="{{ asset('Images/PSU-Logo.png') }}">
    </x-slot:head>
<div class="layout admin-layout">

    {{-- SHARED ADMIN SIDEBAR --}}
    {{-- MAIN CONTENT --}}
    <main class="main">
    <div class="page-wrap">

        <div class="page-head">
            <div>
                <div class="page-heading">Course Approvals</div>
                <div class="page-sub">Review courses and manage their completion badges.</div>
            </div>
            <div class="page-head-tools">
                <input type="search" class="search" id="course-search" placeholder="Search courses" aria-label="Search courses">
                <div class="featured-limit">
                    Featured courses: <strong>{{ $featuredCourseCount }} / {{ \App\Services\CourseModerationService::MAX_FEATURED_COURSES }}</strong>.
                    @if ($featuredCourseCount >= \App\Services\CourseModerationService::MAX_FEATURED_COURSES)
                        Unfeature a course before featuring another.
                    @else
                        You can feature up to {{ \App\Services\CourseModerationService::MAX_FEATURED_COURSES }} courses on the homepage.
                    @endif
                </div>
            </div>
        </div>

        @php
            $count = fn ($key) => $courses->where('status_key', $key)->count();
        @endphp

        <div class="tabs" role="tablist">
            <button type="button" class="tab active" data-filter="all">All<span class="n">{{ $courses->count() }}</span></button>
            <button type="button" class="tab" data-filter="pending">Pending review<span class="n">{{ $count('pending') }}</span></button>
            <button type="button" class="tab" data-filter="approved">Approved<span class="n">{{ $count('approved') }}</span></button>
            <button type="button" class="tab" data-filter="denied">Denied<span class="n">{{ $count('denied') }}</span></button>
        </div>

        <x-flash-toast :message="session('success') ?? $errors->first('feature')" :type="session('success') ? 'success' : 'error'" />

        <div class="course-grid" id="course-grid"
             data-poll-url="{{ route('admin.courses.updates', [], false) }}"
             data-since-at="{{ $coursesCheckedAt->toIso8601String() }}">
            @foreach ($courses as $course)
                @include('admin.courses.card', ['course' => $course])
            @endforeach
        </div>

        <div class="empty-state" id="course-empty" @if($courses->isNotEmpty()) hidden @endif>
            No courses yet. Courses created by faculty will appear here for review.
        </div>
        <div class="empty-state adm-inline-admin-courses-index-1" id="no-match" style="display:none;">
            No courses match your filter.
        </div>

    </div>
    </main>
</div>

<script>
    (function () {
        var tabs = document.querySelectorAll('.tab');
        var courseGrid = document.getElementById('course-grid');
        var search = document.getElementById('course-search');
        var none = document.getElementById('no-match');
        var empty = document.getElementById('course-empty');
        var filter = 'all';

        function apply() {
            var cards = courseGrid.querySelectorAll('.course-card');
            var q = (search ? search.value : '').trim().toLowerCase();
            var shown = 0;
            cards.forEach(function (card) {
                var ok = (filter === 'all' || card.dataset.status === filter) &&
                         (!q || card.dataset.search.indexOf(q) !== -1);
                card.style.display = ok ? '' : 'none';
                if (ok) shown++;
            });
            if (empty) empty.hidden = cards.length > 0;
            if (none) none.style.display = cards.length > 0 && !shown ? 'block' : 'none';
        }

        function adjustTabCount(name, amount) {
            var count = document.querySelector('.tab[data-filter="' + name + '"] .n');
            if (!count) return;
            count.textContent = String(Math.max(0, Number(count.textContent) + amount));
        }

        function findCourseCard(courseId) {
            return Array.from(courseGrid.querySelectorAll('.course-card[data-course-id]'))
                .find(function (existing) { return existing.dataset.courseId === String(courseId); }) || null;
        }

        function placeCourseCard(card) {
            var isPending = card.dataset.status === 'pending';
            var sortTitle = card.dataset.sortTitle || '';
            var cardsInGroup = Array.from(courseGrid.querySelectorAll('.course-card'))
                .filter(function (existing) { return (existing.dataset.status === 'pending') === isPending; });
            var before = cardsInGroup.find(function (existing) {
                return (existing.dataset.sortTitle || '').localeCompare(sortTitle) > 0;
            });
            if (!before && isPending) {
                before = courseGrid.querySelector('.course-card:not([data-status="pending"])');
            }

            courseGrid.insertBefore(card, before);
        }

        function reconcileCourseUpdates(draftIds, html) {
            var changed = false;
            draftIds.forEach(function (courseId) {
                var existing = findCourseCard(courseId);
                if (!existing) return;

                adjustTabCount('all', -1);
                adjustTabCount(existing.dataset.status, -1);
                existing.remove();
                changed = true;
            });

            var template = document.createElement('template');
            template.innerHTML = html;
            var cards = Array.from(template.content.querySelectorAll('.course-card[data-course-id]'));

            cards.forEach(function (card) {
                if (card.dataset.status === 'draft') return;

                var existing = findCourseCard(card.dataset.courseId);
                var previousStatus = existing ? existing.dataset.status : null;
                if (existing) existing.remove();
                placeCourseCard(card);

                if (previousStatus === null) {
                    adjustTabCount('all', 1);
                } else if (previousStatus !== card.dataset.status) {
                    adjustTabCount(previousStatus, -1);
                }
                if (previousStatus !== card.dataset.status) {
                    adjustTabCount(card.dataset.status, 1);
                }
                changed = true;
            });

            if (changed) apply();
        }

        if (!courseGrid) return;

        var coursePollIntervalMs = 5000;
        var maxBackoffMs = 60000;
        var nextDelayMs = coursePollIntervalMs;
        var sinceAt = courseGrid.dataset.sinceAt;
        var timerId = null;
        var requestInFlight = false;
        var resumeAfterRequest = false;
        var disposed = false;
        var hasReportedError = false;
        var activeRequest = null;

        function clearTimer() {
            if (timerId === null) return;
            window.clearTimeout(timerId);
            timerId = null;
        }

        function scheduleNextPoll(delayMs) {
            clearTimer();
            timerId = window.setTimeout(function () {
                timerId = null;
                pollForCourseUpdates();
            }, delayMs);
        }

        async function pollForCourseUpdates() {
            if (disposed || document.hidden || requestInFlight) return;
            requestInFlight = true;
            activeRequest = new AbortController();

            try {
                var url = new URL(courseGrid.dataset.pollUrl, window.location.href);
                url.searchParams.set('sinceAt', sinceAt);
                var response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: activeRequest.signal,
                });
                if (!response.ok) throw new Error('Course approval poll returned HTTP ' + response.status);

                var data = await response.json();
                if (typeof data.courses_html !== 'string'
                    || typeof data.checked_at !== 'string'
                    || !Array.isArray(data.draft_ids)
                    || !data.draft_ids.every(function (courseId) {
                        return Number.isSafeInteger(Number(courseId)) && Number(courseId) > 0;
                    })
                    || !Number.isSafeInteger(Number(data.course_count))) {
                    throw new Error('Course approval poll returned an invalid response.');
                }

                reconcileCourseUpdates(data.draft_ids, data.courses_html);
                sinceAt = data.checked_at;
                nextDelayMs = coursePollIntervalMs;
                hasReportedError = false;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    if (!hasReportedError) {
                        console.error('Unable to poll course approvals.', error);
                        hasReportedError = true;
                    }
                    nextDelayMs = Math.min(nextDelayMs * 2, maxBackoffMs);
                }
            } finally {
                requestInFlight = false;
                activeRequest = null;
                if (disposed || document.hidden) return;

                var delayMs = resumeAfterRequest ? 0 : nextDelayMs;
                resumeAfterRequest = false;
                scheduleNextPoll(delayMs);
            }
        }

        function onVisibilityChange() {
            if (document.hidden) {
                clearTimer();
                return;
            }
            if (requestInFlight) {
                resumeAfterRequest = true;
                return;
            }
            scheduleNextPoll(0);
        }

        function cleanup() {
            disposed = true;
            clearTimer();
            if (activeRequest) activeRequest.abort();
            document.removeEventListener('visibilitychange', onVisibilityChange);
            window.removeEventListener('pagehide', cleanup);
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.toggle('active', t === tab); });
                filter = tab.dataset.filter;
                apply();
            });
        });
        if (search) search.addEventListener('input', apply);
        document.addEventListener('visibilitychange', onVisibilityChange);
        window.addEventListener('pagehide', cleanup);
        apply();
        pollForCourseUpdates();
    })();
</script>

{{-- Back to top --}}
<button id="back-to-top-btn" type="button" title="Back to top" aria-label="Back to top"
        onclick="window.scrollTo({top:0,behavior:'smooth'});">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
</button>

<script>
    (function () {
        var btn = document.getElementById('back-to-top-btn');
        if (!btn) return;
        function t() { btn.style.display = (window.scrollY || document.documentElement.scrollTop) > 400 ? 'flex' : 'none'; }
        window.addEventListener('scroll', t, { passive: true });
        t();
    })();
</script>
</x-admin.layout>
