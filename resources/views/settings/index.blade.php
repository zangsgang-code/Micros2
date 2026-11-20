@php
    $profileRoute = $user->isAdmin()
        ? 'admin.profile'
        : ($user->isFaculty() ? 'faculty.profile' : 'profile.show');
@endphp

@push('styles')
<style>
    .settings-page {
        width: 100%;
        color: #0e1a3a;
    }
    .app-shell.page-settings .main.app-main { padding: 32px clamp(20px, 3vw, 44px) 60px; }
    .settings-page-header { margin-bottom: 24px; }
    .settings-page-header h1 {
        margin: 0 0 7px;
        color: #0e1a3a;
        font-family: "Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, sans-serif;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -.04em;
    }
    .settings-page-header p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.55;
    }
    .settings-card {
        margin-bottom: 18px;
        padding: 22px;
        border: 1px solid #e6eaf2;
        border-radius: 17px;
        background: #fff;
        box-shadow: 0 5px 18px rgba(11, 27, 69, .045);
    }
    .settings-card-header {
        margin-bottom: 8px;
        padding-bottom: 16px;
        border-bottom: 1px solid #eef1f6;
    }
    .settings-card-header h2 {
        margin: 0 0 5px;
        color: #0e1a3a;
        font-size: 16px;
        font-weight: 750;
    }
    .settings-card-header p {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }
    .settings-option {
        display: flex;
        min-height: 68px;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 13px 0;
        border-bottom: 1px solid #eef1f6;
        cursor: pointer;
    }
    .settings-option:last-child { border-bottom: 0; }
    .settings-option-copy { display: flex; flex-direction: column; gap: 4px; }
    .settings-option-copy strong { color: #0e1a3a; font-size: 13px; font-weight: 700; }
    .settings-option-copy span { color: #64748b; font-size: 12px; line-height: 1.45; }
    .settings-toggle { position: relative; display: inline-flex; flex: 0 0 42px; width: 42px; height: 24px; }
    .settings-toggle input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .settings-toggle-track {
        width: 42px;
        height: 24px;
        border-radius: 999px;
        background: #cbd5e1;
        transition: background-color .18s ease;
    }
    .settings-toggle-track::after {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .2);
        content: "";
        transition: transform .18s ease;
    }
    .settings-toggle input:checked + .settings-toggle-track { background: #2447d4; }
    .settings-toggle input:checked + .settings-toggle-track::after { transform: translateX(18px); }
    .settings-toggle input:focus-visible + .settings-toggle-track { outline: 3px solid rgba(36, 71, 212, .25); outline-offset: 2px; }
    .settings-card-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #eef1f6;
    }
    .settings-save {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        padding: 0 18px;
        border: 0;
        border-radius: 10px;
        background: #0b1b45;
        color: #fff;
        font: inherit;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: background-color .18s ease, transform .18s ease;
    }
    .settings-save:hover { background: #2447d4; transform: translateY(-1px); }
    .settings-note { margin: 12px 0 0; color: #64748b; font-size: 11px; line-height: 1.5; }
    .settings-feedback {
        margin-bottom: 16px;
        padding: 11px 14px;
        border: 1px solid #b7e4c7;
        border-radius: 10px;
        background: #effaf3;
        color: #14532d;
        font-size: 13px;
    }
    .settings-feedback.is-error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
    .settings-profile-link { color: #2447d4; font-size: 13px; font-weight: 700; text-decoration: none; }
    .settings-profile-link:hover { text-decoration: underline; text-underline-offset: 3px; }
    .settings-danger-card { border-color: #f3c4c4; background: #fff7f7; }
    .settings-danger-card .settings-card-header { border-bottom-color: #f3d4d4; }
    .settings-danger-card .settings-card-header h2 { color: #b42318; }
    .settings-danger-copy { margin: 0; color: #64748b; font-size: 12px; line-height: 1.6; }
    .settings-delete-form { display: flex; align-items: flex-end; gap: 14px; margin-top: 18px; }
    .settings-delete-field { display: grid; flex: 1; gap: 6px; }
    .settings-delete-field span { color: #334155; font-size: 12px; font-weight: 700; }
    .settings-delete-field input {
        width: 100%;
        min-height: 40px;
        padding: 0 11px;
        border: 1px solid #d8dee9;
        border-radius: 9px;
        background: #fff;
        color: #0e1a3a;
        font: inherit;
        font-size: 13px;
    }
    .settings-delete-field input:focus { border-color: #2447d4; outline: 3px solid rgba(36, 71, 212, .14); }
    .settings-delete-button {
        min-height: 40px;
        padding: 0 15px;
        border: 0;
        border-radius: 9px;
        background: #b42318;
        color: #fff;
        font: inherit;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .settings-delete-button:hover { background: #912018; }
    @media (max-width: 1024px) {
        .app-shell.page-settings .main.app-main { padding: 24px 22px 42px; }
    }
    @media (max-width: 640px) {
        .app-shell.page-settings .main.app-main { padding: 20px 14px 36px; }
        .settings-page-header h1 { font-size: 24px; }
        .settings-card { padding: 17px; }
        .settings-option { gap: 12px; }
        .settings-delete-form { align-items: stretch; flex-direction: column; }
    }
</style>
@endpush

<x-layout :shell="$user->roleName()" title="Settings | UpSkill" page="settings">
    <section class="settings-page" aria-labelledby="settings-page-title">
        <header class="settings-page-header">
            <h1 id="settings-page-title">Settings</h1>
            <p>Manage the in-app notifications that matter to your {{ $user->roleName() }} account.</p>
        </header>

        @if (session('success'))
            <div class="settings-feedback" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="settings-feedback is-error" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <section class="settings-card" aria-labelledby="settings-notifications-title">
                <header class="settings-card-header">
                    <h2 id="settings-notifications-title">In-app notifications</h2>
                    <p>Choose which updates appear in your notification feed, preview, and unread count.</p>
                </header>

                @foreach ($categories as $key => $category)
                    <label class="settings-option">
                        <span class="settings-option-copy">
                            <strong>{{ $category['title'] }}</strong>
                            <span>{{ $category['description'] }}</span>
                        </span>
                        <span class="settings-toggle">
                            <input
                                type="checkbox"
                                name="notifications[{{ $key }}]"
                                value="1"
                                @checked($preferences[$key] ?? true)
                            >
                            <span class="settings-toggle-track" aria-hidden="true"></span>
                        </span>
                    </label>
                @endforeach

                <p class="settings-note">Turning a category off hides it from the in-app feed and unread count. Existing notifications are kept and will reappear if you turn it back on.</p>
                <div class="settings-card-footer">
                    <button class="settings-save" type="submit">Save settings</button>
                </div>
            </section>
        </form>

        @if (! $user->isSubAdmin() || $user->canAccessAdminPage('profile'))
            <section class="settings-card" aria-labelledby="settings-account-title">
                <header class="settings-card-header">
                    <h2 id="settings-account-title">Account details</h2>
                    <p>Update your profile and account information from your Profile page.</p>
                </header>
                <a class="settings-profile-link" href="{{ route($profileRoute) }}">Manage profile</a>
            </section>
        @endif

        @if ($user->isStudent())
            <section class="settings-card settings-danger-card" aria-labelledby="settings-delete-title">
                <header class="settings-card-header">
                    <h2 id="settings-delete-title">Deactivate and delete account</h2>
                </header>
                <p class="settings-danger-copy">
                    Your account will be deactivated immediately. Log in again within 30 days to cancel deletion and reactivate it.
                    After 30 days, personal profile details will be anonymized. Required learning history and credentials may be retained under PSU policy.
                </p>
                <form action="{{ route('settings.account-deletion') }}" method="POST" class="settings-delete-form">
                    @csrf
                    @method('DELETE')
                    <label class="settings-delete-field">
                        <span>Current password</span>
                        <input type="password" name="password" autocomplete="current-password" required>
                    </label>
                    <label class="settings-delete-field">
                        <span>Type DELETE to confirm</span>
                        <input type="text" name="confirmation" autocomplete="off" required>
                    </label>
                    <button class="settings-delete-button" type="submit">Deactivate account</button>
                </form>
            </section>
        @endif
    </section>
</x-layout>
