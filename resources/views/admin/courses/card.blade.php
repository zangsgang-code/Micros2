<div class="course-card {{ $course->status_key === 'pending' ? 'is-pending' : '' }}"
     data-course-id="{{ $course->id }}"
     data-sort-title="{{ strtolower($course->title) }}"
     data-status="{{ $course->status_key }}"
     data-search="{{ strtolower($course->title . ' ' . ($course->instructor ?? '')) }}"
     onclick="window.location='{{ route('admin.courses.show', $course->id) }}'">
    <div class="card-top">
        <div class="card-status-group">
            @if ($course->status_key !== 'approved')
                <span class="status status-{{ $course->status_key }}">
                    <span class="dot"></span>{{ $course->status }}
                </span>
            @endif
            @if ($course->can_toggle_publish)
                <span class="publication-status adm-publication-{{ $course->is_published ? 'published' : 'unpublished' }}">{{ $course->is_published ? 'Published' : 'Unpublished' }}</span>
            @endif
        </div>

        @if ($course->can_toggle_feature || $course->is_featured)
            <form class="star-form" method="POST" action="{{ route('admin.courses.feature', $course->id) }}" onclick="event.stopPropagation();">
                @csrf
                <button type="submit"
                        class="star-btn {{ $course->is_featured ? 'is-on' : '' }}"
                        aria-label="{{ $course->is_featured ? 'Unfeature course' : 'Feature course on homepage' }}"
                        title="{{ $course->is_featured ? 'Shown on the homepage. Click to unfeature.' : 'Feature this course on the homepage' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg>
                </button>
            </form>
        @endif
    </div>

    <div class="course-intro">
        @if ($course->thumbnail_url)
            <img class="course-thumb" src="{{ $course->thumbnail_url }}" alt="" loading="lazy">
        @else
            <div class="course-thumb-placeholder" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m2 9 10-5 10 5-10 5L2 9Z"/><path d="M6 11v5c3.5 2.5 8.5 2.5 12 0v-5M22 9v6"/></svg>
            </div>
        @endif
        <div class="course-intro-copy">
            <div class="course-title">{{ $course->title }}</div>
            <div class="course-by">By {{ $course->instructor ?? 'Faculty' }}</div>
            <div class="course-stats">{{ $course->faculty }} faculty &middot; {{ $course->students }} students</div>
        </div>
    </div>

    <div class="details">
        <div class="detail-row">
            <span class="detail-label">Badge</span>
            <div class="detail-value {{ $course->badge_icon || $course->badge ? '' : 'off' }}">
                @if ($course->badge_icon)
                    <img src="{{ $course->badge_icon }}" alt="">
                @elseif ($course->badge)
                    <span class="blank"></span>
                @endif
                <span>{{ $course->badge ?: 'No badge yet' }}</span>
            </div>
        </div>

        <div class="detail-row">
            <span class="detail-label">Certificate</span>
            <div class="detail-value {{ $course->certificate_enabled ? '' : 'off' }}">
                <span>
                    @if (! $course->certificate_enabled)
                        None
                    @else
                        {{ $course->certificate_mode === 'upload' ? 'Uploaded' : 'Auto-generated' }}
                    @endif
                </span>
            </div>
        </div>

        @if (! empty($course->related_skills))
            <div class="detail-row">
                <span class="detail-label">Related</span>
                <div class="detail-value">
                    <span>
                        {{ implode(', ', array_slice($course->related_skills, 0, 3)) }}
                        @if (count($course->related_skills) > 3) +{{ count($course->related_skills) - 3 }} @endif
                    </span>
                </div>
            </div>
        @endif
    </div>

    <div class="course-progress">
        <div class="progress-head"><span>Completion</span><strong>{{ $course->percent }}%</strong></div>
        <div class="progress-track"><div class="progress-fill" style="width: {{ $course->percent }}%"></div></div>
    </div>

    @if ($course->status_key === 'pending')
        <div class="actions" onclick="event.stopPropagation();">
            <a href="{{ route('admin.courses.show', $course->id) }}" class="btn btn-approve">Review course</a>
        </div>
    @elseif ($course->can_toggle_publish)
        <div class="actions" onclick="event.stopPropagation();">
            <form method="POST" action="{{ route('admin.courses.publish', $course->id) }}">
                @csrf
                <button type="submit" class="btn {{ $course->is_published ? 'btn-unpublish' : 'btn-publish' }}">
                    {{ $course->is_published ? 'Unpublish' : 'Publish' }}
                </button>
            </form>
        </div>
    @else
        <div class="actions" onclick="event.stopPropagation();">
            <a href="{{ route('admin.courses.show', $course->id) }}" class="btn">View details</a>
        </div>
    @endif
</div>
