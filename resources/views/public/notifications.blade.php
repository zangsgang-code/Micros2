<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notifications | UPSKILL</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #111b63;
            --navy-deep: #0a1248;
            --gold: #f3c52f;
            --ink: #17213d;
            --muted: #71809a;
            --line: #e7ebf3;
            --surface: #fff;
            --canvas: #f4f6fb;
            --shadow: 0 14px 36px rgba(19, 32, 83, .07);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scrollbar-width: none; -ms-overflow-style: none; }
        html::-webkit-scrollbar { display: none; width: 0; height: 0; }
        body {
            margin: 0;
            color: var(--ink);
            background: var(--canvas);
            font-family: "Inter", sans-serif;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; cursor: pointer; }
        .page { width: min(1320px, 100%); margin: 0 auto; padding: 34px 28px 64px; }
        .hero {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
            overflow: hidden;
            margin-bottom: 20px;
            padding: 30px 34px;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 24px;
            color: #fff;
            background: radial-gradient(circle at 88% 12%, rgba(80, 107, 221, .45), transparent 34%), linear-gradient(118deg, #0d164d, #1b2f83);
            box-shadow: 0 18px 42px rgba(17, 27, 99, .18);
        }
        .hero::after {
            position: absolute;
            top: -110px;
            right: 15%;
            width: 300px;
            height: 300px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 50%;
            content: "";
            pointer-events: none;
        }
        .hero-copy { position: relative; z-index: 1; display: flex; align-items: center; gap: 18px; }
        .hero-symbol {
            display: grid;
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            place-items: center;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 18px;
            color: var(--gold);
            background: rgba(255,255,255,.1);
        }
        .hero-symbol .material-symbols-rounded { font-size: 30px; }
        .hero-eyebrow { margin: 0 0 5px; color: #f8d85c; font-size: 11px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        .hero h1 { margin: 0; font-size: clamp(25px, 3vw, 34px); font-weight: 800; letter-spacing: -.04em; }
        .hero p:last-child { margin: 7px 0 0; color: rgba(255,255,255,.72); font-size: 14px; line-height: 1.5; }
        .hero-stats { position: relative; z-index: 1; display: flex; gap: 10px; }
        .hero-stat {
            min-width: 118px;
            padding: 13px 17px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 15px;
            background: rgba(255,255,255,.09);
            backdrop-filter: blur(8px);
        }
        .hero-stat strong { display: block; font-size: 21px; line-height: 1.2; }
        .hero-stat span { display: block; margin-top: 4px; color: rgba(255,255,255,.68); font-size: 11px; font-weight: 700; }
        .page-home {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 0 0 18px;
            color: #5d6983;
            font-size: 13px;
            font-weight: 700;
            transition: color .18s ease;
        }
        .page-home:hover { color: var(--navy); }
        .page-home .material-symbols-rounded { font-size: 18px; }
        .content-grid { display: grid; grid-template-columns: 248px minmax(0, 1fr); gap: 20px; align-items: start; }
        .sidebar-card, .list-card { border: 1px solid var(--line); border-radius: 20px; background: var(--surface); box-shadow: var(--shadow); }
        .sidebar-card { padding: 19px 14px; }
        .sidebar-head { padding: 0 10px 14px; }
        .sidebar-head h2 { margin: 0; color: var(--ink); font-size: 15px; font-weight: 800; }
        .sidebar-head p { margin: 5px 0 0; color: var(--muted); font-size: 11px; }
        .filter-list { display: grid; gap: 4px; }
        .filter-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 42px;
            padding: 9px 10px;
            border: 1px solid transparent;
            border-radius: 11px;
            color: #596781;
            font-size: 12px;
            font-weight: 650;
            transition: color .18s ease, background .18s ease, border-color .18s ease;
        }
        .filter-item:hover { color: var(--navy); background: #f6f8fd; }
        .filter-item.active { border-color: #e2e8ff; color: var(--navy); background: #f0f3ff; font-weight: 800; }
        .filter-label { display: flex; align-items: center; gap: 10px; }
        .filter-label .material-symbols-rounded { color: #8792a8; font-size: 18px; }
        .filter-item.active .filter-label .material-symbols-rounded { color: var(--navy); }
        .filter-count {
            display: grid;
            min-width: 23px;
            height: 23px;
            padding: 0 6px;
            place-items: center;
            border-radius: 8px;
            color: #66748f;
            background: #f0f2f7;
            font-size: 10px;
            font-weight: 800;
        }
        .filter-item.active .filter-count { color: #fff; background: var(--navy); }
        .list-card { min-height: 480px; padding: 24px; }
        .list-card-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-bottom: 18px; border-bottom: 1px solid var(--line); }
        .list-card-head h2 { margin: 0; color: var(--ink); font-size: 17px; font-weight: 800; letter-spacing: -.02em; }
        .list-card-head p { margin: 5px 0 0; color: var(--muted); font-size: 12px; }
        .mark-all-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 0 13px;
            border: 1px solid #dfe5f0;
            border-radius: 10px;
            color: var(--navy);
            background: #fff;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
            transition: background .18s ease, border-color .18s ease;
        }
        .mark-all-button:hover:not(:disabled) { border-color: var(--navy); background: #f6f8ff; }
        .mark-all-button:disabled { color: #9ba5b6; cursor: not-allowed; }
        .mark-all-button .material-symbols-rounded { font-size: 17px; }
        .notification-list { display: grid; gap: 10px; padding-top: 16px; }
        .notification-card {
            position: relative;
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) auto;
            align-items: start;
            gap: 14px;
            padding: 16px;
            border: 1px solid #e9edf4;
            border-radius: 15px;
            background: #fff;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }
        .notification-card:hover { border-color: #d7def0; box-shadow: 0 8px 20px rgba(19, 32, 83, .06); transform: translateY(-1px); }
        .notification-card.unread { border-color: #dce4ff; background: linear-gradient(100deg, #f8f9ff, #fff 42%); }
        .notification-icon {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: 13px;
            color: #2741a0;
            background: #edf1ff;
        }
        .notification-icon .material-symbols-rounded { font-size: 22px; }
        .notification-card[data-type="announcement"] .notification-icon { color: #996500; background: #fff5d8; }
        .notification-card[data-type="course"] .notification-icon { color: #176f78; background: #e4f7f5; }
        .notification-card[data-type="enrollment"] .notification-icon { color: #6350a5; background: #f0ebff; }
        .notification-card[data-type="assessment"] .notification-icon { color: #a5502b; background: #fff0e8; }
        .notification-card[data-type="badge"] .notification-icon,
        .notification-card[data-type="certificate"] .notification-icon { color: #9b6b00; background: #fff4d2; }
        .notification-card[data-type="system"] .notification-icon { color: #4c5c78; background: #edf1f6; }
        .notification-copy { min-width: 0; }
        .notification-topline { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-bottom: 5px; }
        .notification-topline strong { color: var(--ink); font-size: 13px; font-weight: 800; line-height: 1.4; overflow-wrap: anywhere; }
        .notification-card.unread .notification-topline strong { color: #101b5d; }
        .category-tag { padding: 4px 7px; border-radius: 6px; color: #65718a; background: #f1f3f7; font-size: 9px; font-weight: 800; line-height: 1; text-transform: uppercase; letter-spacing: .04em; }
        .unread-tag { display: inline-flex; align-items: center; gap: 5px; color: #3349a5; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .unread-tag::before { width: 6px; height: 6px; border-radius: 50%; background: #3e58cd; content: ""; }
        .notification-message { display: -webkit-box; overflow: hidden; color: #64708a; font-size: 12px; line-height: 1.55; overflow-wrap: anywhere; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .notification-meta { display: flex; align-items: center; gap: 7px; margin-top: 8px; color: #8993a6; font-size: 10px; font-weight: 600; }
        .notification-meta .material-symbols-rounded { font-size: 14px; }
        .notification-action { align-self: center; }
        .notification-action a, .notification-action button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-height: 32px;
            padding: 0 10px;
            border: 1px solid #e1e6f0;
            border-radius: 9px;
            color: var(--navy);
            background: #fff;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
            transition: border-color .18s ease, background .18s ease;
        }
        .notification-action a:hover, .notification-action button:hover { border-color: #bec9e5; background: #f7f8ff; }
        .notification-action .material-symbols-rounded { font-size: 15px; }
        .empty-state { display: grid; min-height: 300px; padding: 32px 18px; place-content: center; justify-items: center; text-align: center; }
        .empty-state__icon { display: grid; width: 54px; height: 54px; margin-bottom: 14px; place-items: center; border-radius: 17px; color: #5263aa; background: #eef1ff; }
        .empty-state__icon .material-symbols-rounded { font-size: 27px; }
        .empty-state h3 { margin: 0; color: var(--ink); font-size: 15px; }
        .empty-state p { max-width: 320px; margin: 7px 0 0; color: var(--muted); font-size: 12px; line-height: 1.55; }
        @media (max-width: 900px) {
            .content-grid { grid-template-columns: 1fr; gap: 14px; }
            .sidebar-card { padding: 13px; }
            .sidebar-head { display: none; }
            .filter-list { display: flex; gap: 7px; overflow-x: auto; scrollbar-width: none; }
            .filter-list::-webkit-scrollbar { display: none; }
            .filter-item { flex: 0 0 auto; gap: 9px; min-height: 38px; padding: 7px 10px; border-color: var(--line); background: #fff; }
            .filter-item.active { border-color: #d9e1ff; }
            .filter-label { gap: 7px; }
        }
        @media (max-width: 700px) {
            .page { padding: 22px 14px 42px; }
            .hero { align-items: flex-start; flex-direction: column; gap: 18px; margin-bottom: 16px; padding: 22px 19px; border-radius: 19px; }
            .hero-copy { align-items: flex-start; gap: 12px; }
            .hero-symbol { width: 46px; height: 46px; flex-basis: 46px; border-radius: 14px; }
            .hero-symbol .material-symbols-rounded { font-size: 24px; }
            .hero p:last-child { max-width: 370px; font-size: 12px; }
            .hero-stats { width: 100%; }
            .hero-stat { min-width: 0; flex: 1; padding: 10px 12px; }
            .hero-stat strong { font-size: 18px; }
            .list-card { min-height: 0; padding: 16px 13px; border-radius: 17px; }
            .list-card-head { align-items: flex-start; }
            .list-card-head h2 { font-size: 15px; }
            .mark-all-button { min-height: 34px; padding: 0 9px; font-size: 10px; }
            .notification-list { gap: 8px; padding-top: 12px; }
            .notification-card { grid-template-columns: 38px minmax(0, 1fr); gap: 10px; padding: 12px; border-radius: 13px; }
            .notification-icon { width: 38px; height: 38px; border-radius: 11px; }
            .notification-icon .material-symbols-rounded { font-size: 20px; }
            .notification-action { grid-column: 2; justify-self: start; }
            .notification-action a, .notification-action button { min-height: 29px; }
            .empty-state { min-height: 250px; }
            .announcement-dialog-head, .announcement-dialog-content { padding: 16px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    @php $user = auth()->user(); @endphp
    @include('components.authenticated-topbar', ['user' => $user])

    <main class="page">
        <x-flash-toast :message="session('success')" />

        @php
            $all = collect($notifications);
            $unreadCount = $unreadNotificationCount ?? $all->where('unread', true)->count();
            $totalCount = $all->count();
            $active = $filter ?? 'all';
            $visible = match ($active) {
                'unread' => $all->where('unread', true),
                'course' => $all->where('type', 'course'),
                'enrollment' => $all->where('type', 'enrollment'),
                'assessment' => $all->where('type', 'assessment'),
                'credential' => $all->whereIn('type', ['badge', 'certificate']),
                'announcement' => $all->where('type', 'announcement'),
                'system' => $all->where('type', 'system'),
                default => $all,
            };
            $homeRoute = auth()->check()
                ? (auth()->user()->isAdmin() ? 'admin.dashboard' : (auth()->user()->isFaculty() ? 'faculty.dashboard' : 'dashboard'))
                : 'Homepage';
            $filters = [
                ['key' => 'all', 'label' => 'All activity', 'icon' => 'inbox', 'count' => $totalCount],
                ['key' => 'unread', 'label' => 'Unread', 'icon' => 'mark_email_unread', 'count' => $unreadCount],
                ['key' => 'course', 'label' => 'Courses', 'icon' => 'school', 'count' => $all->where('type', 'course')->count()],
                ['key' => 'enrollment', 'label' => 'Enrollments', 'icon' => 'assignment_ind', 'count' => $all->where('type', 'enrollment')->count()],
                ['key' => 'assessment', 'label' => 'Assessments', 'icon' => 'quiz', 'count' => $all->where('type', 'assessment')->count()],
                ['key' => 'credential', 'label' => 'Credentials', 'icon' => 'workspace_premium', 'count' => $all->whereIn('type', ['badge', 'certificate'])->count()],
                ['key' => 'announcement', 'label' => 'Announcements', 'icon' => 'campaign', 'count' => $all->where('type', 'announcement')->count()],
                ['key' => 'system', 'label' => 'System', 'icon' => 'info', 'count' => $all->where('type', 'system')->count()],
            ];
        @endphp

        <section class="hero" aria-labelledby="notifications-heading">
            <div class="hero-copy">
                <div class="hero-symbol"><span class="material-symbols-rounded" aria-hidden="true">notifications_active</span></div>
                <div>
                    <p class="hero-eyebrow">Your activity</p>
                    <h1 id="notifications-heading">Notifications</h1>
                    <p>Stay up to date with courses, reviews, credentials, and announcements.</p>
                </div>
            </div>
            <div class="hero-stats" aria-label="Notification summary">
                <div class="hero-stat"><strong data-unread-notification-count>{{ $unreadCount }}</strong><span>Unread</span></div>
                <div class="hero-stat"><strong>{{ $totalCount }}</strong><span>Total updates</span></div>
            </div>
        </section>

        <a href="{{ route($homeRoute) }}" class="page-home"><span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>Back to home</a>

        <div class="content-grid">
            <aside class="sidebar-card" aria-label="Notification categories">
                <div class="sidebar-head">
                    <h2>Categories</h2>
                    <p>Filter your activity</p>
                </div>
                <nav class="filter-list">
                    @foreach ($filters as $filterOption)
                        <a class="filter-item {{ $active === $filterOption['key'] ? 'active' : '' }}"
                            href="{{ $filterOption['key'] === 'all' ? route('notifications.index') : route('notifications.index', ['filter' => $filterOption['key']]) }}"
                            @if ($active === $filterOption['key']) aria-current="page" @endif>
                            <span class="filter-label"><span class="material-symbols-rounded" aria-hidden="true">{{ $filterOption['icon'] }}</span>{{ $filterOption['label'] }}</span>
                            <span class="filter-count" @if ($filterOption['key'] === 'unread') data-unread-notification-count @endif>{{ $filterOption['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </aside>

            <section class="list-card" aria-labelledby="updates-heading">
                <div class="list-card-head">
                    <div>
                        <h2 id="updates-heading">Latest updates</h2>
                        <p>Your newest activity, all in one place.</p>
                    </div>
                    @auth
                        <form method="POST" action="{{ route('notifications.readAll') }}">
                            @csrf
                            <button class="mark-all-button" type="submit" @disabled($unreadCount === 0)>
                                <span class="material-symbols-rounded" aria-hidden="true">done_all</span>Mark all read
                            </button>
                        </form>
                    @endauth
                </div>
                @forelse ($visible as $notification)
                    <article class="notification-card {{ $notification->unread ? 'unread' : '' }}" data-type="{{ $notification->type }}">
                        <div class="notification-icon" aria-hidden="true">
                            <span class="material-symbols-rounded">
                                @switch($notification->type)
                                    @case('course') school @break
                                    @case('enrollment') assignment_ind @break
                                    @case('assessment') quiz @break
                                    @case('badge')
                                    @case('certificate') workspace_premium @break
                                    @case('announcement') campaign @break
                                    @case('system') info @break
                                    @default notifications
                                @endswitch
                            </span>
                        </div>
                        <div class="notification-copy">
                            <div class="notification-topline">
                                <strong>{{ $notification->title }}</strong>
                                <span class="category-tag">{{ $notification->type === 'badge' || $notification->type === 'certificate' ? 'Credential' : ucfirst($notification->type) }}</span>
                                @if ($notification->unread)<span class="unread-tag">Unread</span>@endif
                            </div>
                            <div class="notification-message">{{ $notification->message }}</div>
                            <div class="notification-meta">
                                <span class="material-symbols-rounded" aria-hidden="true">schedule</span>{{ $notification->time }}
                            </div>
                            @if ($notification->type === 'announcement')
                                <template id="announcement-content-{{ $notification->entity_id }}"><div class="ck-content rich-content">{!! $notification->content !!}</div></template>
                            @endif
                        </div>
                        @if ($notification->type === 'announcement')
                            <div class="notification-action">
                                <button type="button" data-view-announcement="{{ $notification->entity_id }}" data-title="{{ $notification->title }}" data-time="{{ $notification->time }}">
                                    View announcement<span class="material-symbols-rounded" aria-hidden="true">arrow_outward</span>
                                </button>
                            </div>
                        @elseif (!empty($notification->url))
                            <div class="notification-action">
                                <a href="{{ $notification->url }}">View<span class="material-symbols-rounded" aria-hidden="true">arrow_outward</span></a>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="empty-state">
                        <div class="empty-state__icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $active === 'unread' ? 'task_alt' : 'notifications_none' }}</span></div>
                        <h3>{{ $active === 'unread' ? "You're all caught up" : 'No notifications to show' }}</h3>
                        <p>{{ $active === 'unread' ? 'There are no unread updates right now. New activity will appear here.' : 'There are no updates in this category yet. Try another filter or check back later.' }}</p>
                    </div>
                @endforelse
            </section>
        </div>
    </main>


    {{-- Shared responsiveness layer (drawer nav + grid stacking) --}}
    @include('components.responsive')
</body>
</html>
