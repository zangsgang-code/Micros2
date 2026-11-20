<style>
    /* ================================
       SIDEBAR LAYOUT
       ================================ */

    html,
    body {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
        width: 0;
        height: 0;
        display: none;
    }

    .layout.student-sidebar-layout {
        display: grid;
        grid-template-columns: 244px minmax(0, 1fr);
        align-items: stretch;
        min-height: calc(100vh - 66px);
        transition: grid-template-columns .25s ease;
    }

    .layout.student-sidebar-layout.sidebar-collapsed {
        grid-template-columns: 78px minmax(0, 1fr);
    }


    /* ================================
       MAIN SIDEBAR
       ================================ */

    .layout.student-sidebar-layout .student-sidebar {
        background: #fff;

        padding: 24px 10px 20px;

        display: flex;
        flex-direction: column;
        gap: 5px;

        border-right: 1px solid #e5e7eb;

        height: calc(100vh - 70px);

        position: sticky;
        top: 66px;
        z-index: 900;

        align-self: start;

        overflow-x: hidden;
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;

        transition:
            padding .25s ease,
            width .25s ease;
    }

    .layout.student-sidebar-layout .student-sidebar::-webkit-scrollbar {
        display: none;
    }


    /* ================================
       SIDEBAR LINKS
       ================================ */

    .student-sidebar .side-link {
        display: flex;
        align-items: center;
        flex: 0 0 56px;

        gap: 15px;

        padding: 10px 14px;

        border-radius: 13px;

        color: #334155;

        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.25;

        text-decoration: none;

        white-space: nowrap;
        overflow: hidden;

        transition:
            background-color .2s ease,
            color .2s ease,
            transform .15s ease;
    }


    /* Hover */

    .student-sidebar .side-link:not(.active):hover {
        background: #f2f7ff;
        color: #0d1b6e;
    }

    .student-sidebar .side-link:not(.active):active {
        transform: scale(.98);
    }


    /* ================================
       ACTIVE ITEM
       ================================ */

    .student-sidebar .side-link.active {
        position: relative;
        margin-left: -10px;
        padding: 7px 14px 7px 24px;
        border-radius: 0 13px 13px 0;
        background: transparent;
        color: #071550;
        font-weight: 600;
        box-shadow: none;
        overflow: visible;
    }

    .student-sidebar .side-link.active::before {
        position: absolute;
        z-index: 0;
        top: 5px;
        right: 0;
        bottom: 5px;
        left: 0;
        border-radius: 0 9px 9px 0;
        background: #dbeafe;
        box-shadow: 0 0 24px 6px rgba(36, 71, 212, .12);
        content: "";
    }

    .student-sidebar .side-link.active::after {
        position: absolute;
        z-index: 1;
        top: 5px;
        bottom: 5px;
        left: 0;
        width: 5px;
        border-radius: 0 5px 5px 0;
        background: #2447d4;
        content: "";
    }

    .student-sidebar .side-link.active > * {
        position: relative;
        z-index: 2;
    }

    .student-sidebar .side-link.active:hover {
        background: transparent;
        color: #071550;
    }


    /* ================================
       ICON CONTAINER
       ================================ */

    .student-sidebar .side-icon-box {
        width: 30px;
        height: 30px;

        flex: 0 0 30px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: transparent !important;
        border: 0;
        border-radius: 0;
    }

    .student-sidebar .side-icon-svg {
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        color: inherit;
        transition: color .2s ease, transform .2s ease;
    }

    .student-sidebar .side-link:not(.active):hover .side-icon-svg {
        color: #2447d4;
    }


    /* Normal icon */

    /* ================================
       LABEL
       ================================ */

    .student-sidebar .side-label {
        overflow: hidden;
        flex: 0 0 auto;
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.25;

        opacity: 1;

        transition:
            opacity .18s ease,
            width .25s ease;
    }

    .student-sidebar .sidebar-section-label {
        flex: 0 0 auto;
        margin: 12px 14px 3px;
        color: #64748b;
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .09em;
        line-height: 1.3;
        text-transform: uppercase;
        white-space: nowrap;
    }


    /* ================================
       COLLAPSED SIDEBAR
       ================================ */

    .student-sidebar-layout.sidebar-collapsed
    .student-sidebar .side-link {
        justify-content: center;

        gap: 0;

        padding-left: 8px;
        padding-right: 8px;
    }


    .student-sidebar-layout.sidebar-collapsed
    .student-sidebar .side-label {
        width: 0;

        opacity: 0;
    }

    .student-sidebar-layout.sidebar-collapsed
    .student-sidebar .sidebar-section-label {
        display: none;
    }


    /* ================================
       MAIN CONTENT
       ================================ */

    .student-sidebar-layout > .main {
        padding: 28px 32px 48px;

        min-width: 0;

        transition: padding .25s ease;
    }



    /* ================================
       MOBILE
       ================================ */

    @media (max-width: 1024px) {

        .layout.student-sidebar-layout,
        .layout.student-sidebar-layout.sidebar-collapsed {
            grid-template-columns: 1fr;
        }

        .layout.student-sidebar-layout .student-sidebar {
            position: static;

            height: auto;

            flex-direction: row;
            align-items: center;

            overflow-x: auto;

            padding: 10px 14px;

            border-right: 0;
            border-bottom: 1px solid var(--line, #e5e7eb);
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .layout.student-sidebar-layout .student-sidebar::-webkit-scrollbar {
            display: none;
        }

        .student-sidebar-layout.sidebar-collapsed .student-sidebar {
            display: none;
        }

        .student-sidebar .side-link {
            flex: 0 0 auto;

            min-height: 48px;
        }

        .student-sidebar .side-link.active {
            min-height: 42px;
            margin-left: 0;
            padding: 6px 14px;
            border-radius: 0 9px 9px 0;
        }

        .student-sidebar .side-link.active::before {
            border-radius: 0 9px 9px 0;
        }

        .student-sidebar .side-label,
        .student-sidebar-layout.sidebar-collapsed
        .student-sidebar .side-label {
            width: auto;
            opacity: 1;
        }

        .student-sidebar .sidebar-section-label {
            display: none;
        }

        .student-sidebar-layout.sidebar-collapsed
        .student-sidebar .side-link {
            justify-content: flex-start;

            gap: 15px;

            padding: 10px 14px;
        }

        .student-sidebar-layout > .main {
            padding: 24px 22px 42px;
        }
    }

    /* Phones and tablets: the links move into the floating circle menu
       (built by components/responsive), so the horizontal bar is hidden.
       Hiding it also removes the empty band it left above the page title. */
    @media (max-width: 1024px) {
        .layout.student-sidebar-layout .student-sidebar {
            display: none !important;
        }

        .layout.student-sidebar-layout {
            align-content: start;
        }
    }

    body.app-shell--student .student-sidebar-layout > .main.app-main .ui-page-header :where(.ui-page-title, h1, h2) {
        color: #0e1a3a;
        font-family: "Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, sans-serif;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: 1.2;
    }

    @media (max-width: 640px) {
        body.app-shell--student .student-sidebar-layout > .main.app-main .ui-page-header :where(.ui-page-title, h1, h2) {
            font-size: 24px;
        }
    }
</style>

<aside class="student-sidebar" id="student-sidebar">
    <span class="sidebar-section-label">Overview</span>
    <a href="{{ route('dashboard') }}" class="side-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"/><path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"/></svg></span>
        <span class="side-label">Dashboard</span>
    </a>
    <span class="sidebar-section-label">Learning</span>
    <a href="{{ route('courses.browse') }}" class="side-link {{ request()->routeIs('courses.browse') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.161 2.58a1.875 1.875 0 0 1 1.678 0l4.993 2.498c.106.052.23.052.336 0l3.869-1.935A1.875 1.875 0 0 1 21.75 4.82v12.485c0 .71-.401 1.36-1.037 1.677l-4.875 2.437a1.875 1.875 0 0 1-1.676 0l-4.994-2.497a.375.375 0 0 0-.336 0l-3.868 1.935A1.875 1.875 0 0 1 2.25 19.18V6.695c0-.71.401-1.36 1.036-1.677l4.875-2.437ZM9 6a.75.75 0 0 1 .75.75V15a.75.75 0 0 1-1.5 0V6.75A.75.75 0 0 1 9 6Zm6.75 3a.75.75 0 0 0-1.5 0v8.25a.75.75 0 0 0 1.5 0V9Z" clip-rule="evenodd"/></svg></span>
        <span class="side-label">Browse Courses</span>
    </a>
    <a href="{{ route('courses.enrolled') }}" class="side-link {{ request()->routeIs('courses.enrolled') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.25 4.533A9.707 9.707 0 0 0 6 3a9.735 9.735 0 0 0-3.25.555.75.75 0 0 0-.5.707v14.25a.75.75 0 0 0 1 .707A8.237 8.237 0 0 1 6 18.75c1.995 0 3.823.707 5.25 1.886V4.533ZM12.75 20.636A8.214 8.214 0 0 1 18 18.75c.966 0 1.89.166 2.75.47a.75.75 0 0 0 1-.708V4.262a.75.75 0 0 0-.5-.707A9.735 9.735 0 0 0 18 3a9.707 9.707 0 0 0-5.25 1.533v16.103Z"/></svg></span>
        <span class="side-label">Enrolled Courses</span>
    </a>
    <span class="sidebar-section-label">Achievements</span>
    <a href="{{ route('badges.index') }}" class="side-link {{ request()->routeIs('badges.*') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.7 2.805a.75.75 0 0 1 .6 0A60.65 60.65 0 0 1 22.83 8.72a.75.75 0 0 1-.231 1.337 49.948 49.948 0 0 0-9.902 3.912l-.003.002c-.114.06-.227.119-.34.18a.75.75 0 0 1-.707 0A50.88 50.88 0 0 0 7.5 12.173v-.224c0-.131.067-.248.172-.311a54.615 54.615 0 0 1 4.653-2.52.75.75 0 0 0-.65-1.352 56.123 56.123 0 0 0-4.78 2.589 1.858 1.858 0 0 0-.859 1.228 49.803 49.803 0 0 0-4.634-1.527.75.75 0 0 1-.231-1.337A60.653 60.653 0 0 1 11.7 2.805Z"/><path d="M13.06 15.473a48.45 48.45 0 0 1 7.666-3.282c.134 1.414.22 2.843.255 4.284a.75.75 0 0 1-.46.711 47.87 47.87 0 0 0-8.105 4.342.75.75 0 0 1-.832 0 47.87 47.87 0 0 0-8.104-4.342.75.75 0 0 1-.461-.71c.035-1.442.121-2.87.255-4.286.921.304 1.83.634 2.726.99v1.27a1.5 1.5 0 0 0-.14 2.508c-.09.38-.222.753-.397 1.11.452.213.901.434 1.346.66a6.727 6.727 0 0 0 .551-1.607 1.5 1.5 0 0 0 .14-2.67v-.645a48.549 48.549 0 0 1 3.44 1.667 2.25 2.25 0 0 0 2.12 0Z"/><path d="M4.462 19.462c.42-.419.753-.89 1-1.395.453.214.902.435 1.347.662a6.742 6.742 0 0 1-1.286 1.794.75.75 0 0 1-1.06-1.06Z"/></svg></span>
        <span class="side-label">My Badges</span>
    </a>
    <a href="{{ route('certificates.index') }}" class="side-link {{ request()->routeIs('certificates.*') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0 1 18 9.375v9.375a3 3 0 0 0 3-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 0 0-.673-.05A3 3 0 0 0 15 1.5h-1.5a3 3 0 0 0-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6ZM13.5 3A1.5 1.5 0 0 0 12 4.5h4.5A1.5 1.5 0 0 0 15 3h-1.5Z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V9.375Zm9.586 4.594a.75.75 0 0 0-1.172-.938l-2.476 3.096-.908-.907a.75.75 0 0 0-1.06 1.06l1.5 1.5a.75.75 0 0 0 1.116-.062l3-3.75Z" clip-rule="evenodd"/></svg></span>
        <span class="side-label">Certificates</span>
    </a>
    <span class="sidebar-section-label">Progress</span>
    <a href="{{ route('pathways.index') }}" class="side-link {{ request()->routeIs('pathways.*') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.161 2.58a1.875 1.875 0 0 1 1.678 0l4.993 2.498c.106.052.23.052.336 0l3.869-1.935A1.875 1.875 0 0 1 21.75 4.82v12.485c0 .71-.401 1.36-1.037 1.677l-4.875 2.437a1.875 1.875 0 0 1-1.676 0l-4.994-2.497a.375.375 0 0 0-.336 0l-3.868 1.935A1.875 1.875 0 0 1 2.25 19.18V6.695c0-.71.401-1.36 1.036-1.677l4.875-2.437ZM9 6a.75.75 0 0 1 .75.75V15a.75.75 0 0 1-1.5 0V6.75A.75.75 0 0 1 9 6Zm6.75 3a.75.75 0 0 0-1.5 0v8.25a.75.75 0 0 0 1.5 0V9Z" clip-rule="evenodd"/></svg></span>
        <span class="side-label">My Pathways</span>
    </a>
    <a href="{{ route('stacking.progress') }}" class="side-link {{ request()->routeIs('stacking.*') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5.566 4.657A4.505 4.505 0 0 1 6.75 4.5h10.5c.41 0 .806.055 1.183.157A3 3 0 0 0 15.75 3h-7.5a3 3 0 0 0-2.684 1.657ZM2.25 12a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3v-6ZM5.25 7.5c-.41 0-.806.055-1.184.157A3 3 0 0 1 6.75 6h10.5a3 3 0 0 1 2.683 1.657A4.505 4.505 0 0 0 18.75 7.5H5.25Z"/></svg></span>
        <span class="side-label">Stacking Progress</span>
    </a>
    <a href="{{ route('analytics.index') }}" class="side-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
        <span class="side-icon-box"><svg class="side-icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z"/></svg></span>
        <span class="side-label">Analytics</span>
    </a>
</aside>

<script>
    (function () {
        var layout = document.querySelector('.student-sidebar-layout');
        var toggle = document.getElementById('sidebar-toggle');
        if (!layout || !toggle) return;

        var collapsed = window.localStorage.getItem('student-sidebar-collapsed') === 'true';
        function updateSidebar() {
            layout.classList.toggle('sidebar-collapsed', collapsed);
            toggle.setAttribute('aria-expanded', String(!collapsed));
            toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        }
        toggle.addEventListener('click', function () {
            collapsed = !collapsed;
            window.localStorage.setItem('student-sidebar-collapsed', String(collapsed));
            updateSidebar();
        });
        updateSidebar();
    })();
</script>
