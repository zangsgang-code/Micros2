<x-admin.layout title="Recognition Requests | UPSKILL" page="admin-academic-credit-recognition" :user="$user" :use-auth-user="false">
    <x-slot:head>
        <link rel="icon" type="image/png" href="{{ asset('Images/PSU-Logo.png') }}">
    </x-slot:head>
    <div class="layout admin-layout">
        <main class="main recognition-main">
            <div class="recognition-content">
                <header class="recognition-head">
                    <h1>Academic Credit Recognition Requests</h1>
                    <p>Review student requests and generate supporting evidence reports for internal UPSKILL use. The platform does not award academic credit or record external institutional decisions.</p>
                </header>

                <x-flash-toast :message="session('success')" />
                @if($errors->any())<div class="notice err" role="alert">{{ $errors->first() }}</div>@endif

                <form method="GET" action="{{ route('admin.academic-credit-recognition') }}" class="recognition-filters">
                    <label>
                        <span>Search requests</span>
                        <input type="search" name="search" value="{{ $search }}" placeholder="Student name, ID, email, or framework">
                    </label>
                    <label>
                        <span>Request status</span>
                        <select name="status">
                            <option value="all" @selected($status === 'all')>All requests</option>
                            <option value="submitted" @selected($status === 'submitted')>Submitted</option>
                            <option value="under_review" @selected($status === 'under_review')>Under Review</option>
                            <option value="needs_information" @selected($status === 'needs_information')>Needs Information</option>
                            <option value="report_generated" @selected($status === 'report_generated')>Report Generated</option>
                            <option value="legacy" @selected($status === 'legacy')>Historical legacy records</option>
                        </select>
                    </label>
                    <button type="submit">Search / Filter</button>
                    @if($search !== '' || $status !== 'all')
                        <a href="{{ route('admin.academic-credit-recognition') }}">Clear</a>
                    @endif
                </form>

                <div class="recognition-result-count">{{ $recognitions->total() }} request{{ $recognitions->total() === 1 ? '' : 's' }}</div>
                <section class="recognition-list">
                    @forelse($recognitions as $record)
                        @php
                            $internalStatus = in_array($record->status, ['submitted', 'under_review', 'needs_information', 'report_generated'], true);
                            $savedFramework = data_get($record->framework_snapshot, 'framework', []);
                            $frameworkName = data_get($savedFramework, 'name') ?: ($record->framework?->name ?? 'Framework unavailable');
                            $frameworkVersion = data_get($savedFramework, 'version_number') ?: ($record->framework?->version_number ?? '—');
                            $statusLabel = $internalStatus ? \Illuminate\Support\Str::headline($record->status) : 'Historical record';
                        @endphp
                        <article class="recognition-card">
                            <div class="recognition-grid">
                                <div class="meta"><span>Student</span><strong>{{ $record->user?->name ?? 'Account unavailable' }}</strong><small>{{ $record->user?->student_id ?: $record->user?->user_code ?: 'No student ID' }}</small></div>
                                <div class="meta"><span>Framework</span><strong>{{ $frameworkName }}</strong><small>Version {{ $frameworkVersion }}</small></div>
                                <div class="meta"><span>Request status</span><strong><span class="status {{ $record->status === 'report_generated' ? 'done' : '' }}">{{ $statusLabel }}</span></strong></div>
                                <div class="meta"><span>Submitted</span><strong>{{ $record->created_at?->format('M j, Y') ?? '—' }}</strong><small>{{ $record->created_at?->format('g:i A') }}</small></div>
                            </div>
                            <div class="actions">
                                <a class="recognition-action" href="{{ route('admin.academic-credit-recognition.show', $record->id) }}">{{ $internalStatus ? 'Review request' : 'View historical record' }}</a>
                            </div>
                        </article>
                    @empty
                        <div class="empty-card">No recognition requests match these filters.</div>
                    @endforelse
                </section>
                <div class="recognition-pagination">{{ $recognitions->links() }}</div>
            </div>
        </main>
    </div>
</x-admin.layout>
