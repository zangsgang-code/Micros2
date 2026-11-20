<style>
    /* Sidebar hover = the student sidebar hover (student-sidebar.blade.php),
       so every role's sidebar reacts with the same colour. The rest of the
       faculty sidebar is styled in resources/css/faculty.css; !important
       makes sure this hover wins over anything set there. */
    aside.faculty-sidebar .side-link {
        transition: background-color .2s ease, color .2s ease, transform .15s ease;
    }
    aside.faculty-sidebar .side-link:not(.active):hover {
        background: #f2f7ff !important;
        color: #0d1b6e !important;
    }
    aside.faculty-sidebar .side-link .side-icon-svg {
        transition: color .2s ease;
    }
    aside.faculty-sidebar .side-link:not(.active):hover .side-icon-svg {
        color: #2447d4 !important;
    }

    html[data-faculty-page] .faculty-sidebar-layout > .main :is(.page-head.faculty-page-heading, .page-header.faculty-page-heading) :where(.faculty-page-title, .ui-page-title, h1, h2) {
        margin: 0 0 7px;
        color: #0e1a3a;
        font-family: "Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, sans-serif;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: 1.2;
    }

    html[data-faculty-page] .faculty-sidebar-layout > .main :is(.page-head.faculty-page-heading, .page-header.faculty-page-heading) .faculty-page-subtitle {
        margin: 0;
        color: #64748b;
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        font-size: 14px;
        font-weight: 400;
        line-height: 1.55;
    }

    @media (max-width: 640px) {
        html[data-faculty-page] .faculty-sidebar-layout > .main :is(.page-head.faculty-page-heading, .page-header.faculty-page-heading) :where(.faculty-page-title, .ui-page-title, h1, h2) {
            font-size: 24px;
        }
    }
</style>

<aside class="faculty-sidebar" id="faculty-sidebar">
    <span class="sidebar-section-label">Overview</span>
    <a href="{{ route('faculty.dashboard') }}" class="side-link {{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"/><path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"/></svg></span>
        <span class="side-label">Dashboard</span>
    </a>
    <span class="sidebar-section-label">Courses</span>
    <a href="{{ route('faculty.courses') }}" class="side-link {{ request()->routeIs('faculty.courses') ? 'active' : '' }}" data-sidebar-alert-target="faculty_my_courses" data-sidebar-alert-label="Assignments awaiting review">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.25 4.533A9.707 9.707 0 0 0 6 3a9.735 9.735 0 0 0-3.25.555.75.75 0 0 0-.5.707v14.25a.75.75 0 0 0 1 .707A8.237 8.237 0 0 1 6 18.75c1.995 0 3.823.707 5.25 1.886V4.533ZM12.75 20.636A8.214 8.214 0 0 1 18 18.75c.966 0 1.89.166 2.75.47a.75.75 0 0 0 1-.708V4.262a.75.75 0 0 0-.5-.707A9.735 9.735 0 0 0 18 3a9.707 9.707 0 0 0-5.25 1.533v16.103Z"/></svg></span>
        <span class="side-label">My Courses</span>
        @if ($facultyMyCoursesNeedsAttention ?? false)
            <span class="side-alert-dot" data-sidebar-alert-dot role="img" aria-label="Assignments awaiting review"></span>
        @endif
    </a>
    <a href="{{ route('faculty.create') }}" class="side-link {{ request()->routeIs('faculty.create') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0 1 18 9.375v9.375a3 3 0 0 0 3-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 0 0-.673-.05A3 3 0 0 0 15 1.5h-1.5a3 3 0 0 0-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6ZM13.5 3A1.5 1.5 0 0 0 12 4.5h4.5A1.5 1.5 0 0 0 15 3h-1.5Z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V9.375Zm9.586 4.594a.75.75 0 0 0-1.172-.938l-2.476 3.096-.908-.907a.75.75 0 0 0-1.06 1.06l1.5 1.5a.75.75 0 0 0 1.116-.062l3-3.75Z" clip-rule="evenodd"/></svg></span>
        <span class="side-label">Create Courses</span>
    </a>
    <span class="sidebar-section-label">People</span>
    <a href="{{ route('faculty.students') }}" class="side-link {{ request()->routeIs('faculty.students*') ? 'active' : '' }}" data-sidebar-alert-target="faculty_learner_progress" data-sidebar-alert-label="Learner progress needs attention">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.5 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM14.25 8.625a3.375 3.375 0 1 1 6.75 0 3.375 3.375 0 0 1-6.75 0ZM1.5 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM17.25 19.128l-.001.144a2.25 2.25 0 0 1-.233.96 10.088 10.088 0 0 0 5.06-1.01.75.75 0 0 0 .42-.643 4.875 4.875 0 0 0-6.957-4.611 8.586 8.586 0 0 1 1.71 5.157v.003Z"/></svg></span><span class="side-label">Learner Progress</span>
        @if ($facultyLearnerProgressNeedsAttention ?? false)
            <span class="side-alert-dot" data-sidebar-alert-dot role="img" aria-label="Learner progress needs attention"></span>
        @endif
    </a>
    <span class="sidebar-section-label">Insights</span>
    <a href="{{ route('faculty.analytics') }}" class="side-link {{ request()->routeIs('faculty.analytics') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z"/></svg></span>
        <span class="side-label">Analytics</span>
    </a>
    <span class="sidebar-section-label">Account</span>
    <a href="{{ route('faculty.profile') }}" class="side-link {{ request()->routeIs('faculty.profile') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z"/><path d="M3.75 21a8.25 8.25 0 0 1 16.5 0h-16.5Z"/></svg></span>
        <span class="side-label">Profile</span>
    </a>
</aside>

<script>
    (function () {
        var layout = document.querySelector('.faculty-sidebar-layout');
        var toggle = document.getElementById('faculty-sidebar-toggle');
        if (!layout || !toggle) return;

        var collapsed = window.localStorage.getItem('faculty-sidebar-collapsed') === 'true';
        function updateSidebar() {
            layout.classList.toggle('sidebar-collapsed', collapsed);
            toggle.setAttribute('aria-expanded', String(!collapsed));
            toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        }
        toggle.addEventListener('click', function () {
            collapsed = !collapsed;
            window.localStorage.setItem('faculty-sidebar-collapsed', String(collapsed));
            updateSidebar();
        });
        updateSidebar();
    })();
</script>
