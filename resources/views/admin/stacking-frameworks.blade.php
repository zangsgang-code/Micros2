
<x-admin.layout title="Stacking Frameworks | UPSKILL" page="admin-stacking-frameworks" :user="$user" :use-auth-user="false">
    <x-slot:head>
        <link rel="icon" type="image/png" href="{{ asset('Images/PSU-Logo.png') }}">
            <link rel="apple-touch-icon" href="{{ asset('Images/PSU-Logo.png') }}">
    </x-slot:head>
<div class="layout admin-layout">
        <main class="main framework-main">
            <div class="framework-content">
                <header class="framework-header">
                    <div>
                        <h1>Stacking frameworks</h1>
                        <p>Manage documented framework versions. Completion and reports do not award academic credit.</p>
                    </div>
                    <div class="framework-header-actions">
                        <button type="button" class="framework-button primary" data-open-framework-modal="create-framework-modal">+ Create draft</button>
                    </div>
                </header>

                <x-flash-toast :message="session('success')" />
                @if($errors->any())
                    <div class="notice notice-error">{{ $errors->first() }}</div>
                @endif

                <div class="framework-section-title">
                    <h2>Configured frameworks</h2>
                    <span>{{ $frameworks->count() }} configured</span>
                </div>

                <section class="framework-list">
                    @forelse($frameworks as $framework)
                        @php
                            $statusClass = match ($framework->status) {
                                'approved' => 'approved',
                                'inactive' => 'inactive',
                                'pending_approval' => 'pending',
                                default => '',
                            };
                            $statusLabel = match ($framework->status) {
                                'approved' => 'Active',
                                'inactive' => 'Archived',
                                default => 'Draft',
                            };
                            $description = \Illuminate\Support\Str::limit(strip_tags((string) $framework->description), 150);
                            $startsRevision = $framework->status === 'inactive';
                            $hasNewerVersion = $framework->newerVersions->isNotEmpty();
                        @endphp

                        <article class="framework-card">
                            <div class="framework-card-top">
                                <div class="framework-card-title">
                                    <h3>{{ $framework->name }}</h3>
                                    <p class="framework-description">{{ $description !== '' ? $description : 'No description provided.' }}</p>
                                    <div class="framework-badges">
                                        <span class="framework-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                                        <span class="framework-pill">Version {{ $framework->version_number }}</span>
                                        @if($framework->pqf_level)<span class="framework-pill">PQF {{ $framework->pqf_level }}</span>@endif
                                    </div>
                                </div>
                                <div class="framework-card-action">
                                    <button type="button" class="framework-button secondary" data-open-framework-modal="edit-framework-modal-{{ $framework->id }}">
                                        {{ $framework->status === 'approved' || ($startsRevision && $hasNewerVersion) ? 'View' : ($startsRevision ? 'Create new version' : 'Edit draft') }}
                                    </button>
                                </div>
                            </div>

                            <div class="framework-info">
                                <div class="framework-info-item"><span>Intended recognition</span><strong>{{ $framework->target_recognition ?: '—' }}</strong></div>
                                <div class="framework-info-item"><span>Required microcredentials</span><strong>{{ $framework->requirements_count }}</strong></div>
                                <div class="framework-info-item"><span>Academic unit</span><strong>{{ $framework->approving_academic_unit ?: '—' }}</strong></div>
                                <div class="framework-info-item"><span>Approval reference</span><strong>{{ $framework->approval_reference ?: 'Not recorded' }}</strong></div>
                                <div class="framework-info-item"><span>Effective period</span><strong>{{ $framework->effective_date?->format('M j, Y') ?? 'Not recorded' }}{{ $framework->expiration_date ? ' – '.$framework->expiration_date->format('M j, Y') : '' }}</strong></div>
                                @if($framework->previousVersion)
                                    <div class="framework-info-item"><span>Previous version</span><strong>Version {{ $framework->previousVersion->version_number }}</strong></div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="empty-state">No stacking frameworks have been configured yet.</div>
                    @endforelse
                </section>
            </div>
        </main>
    </div>

    {{-- Create modal --}}
    <div class="framework-modal" id="create-framework-modal" aria-hidden="true">
        <div class="framework-modal-card" role="dialog" aria-modal="true" aria-labelledby="create-framework-modal-title">
            <div class="framework-modal-head">
                <h2 id="create-framework-modal-title">Create framework draft</h2>
                <button type="button" class="framework-modal-close" data-close-framework-modal aria-label="Close">&times;</button>
            </div>
            <div class="framework-modal-body">
                <form method="POST" action="{{ route('admin.stacking-frameworks.store') }}" id="create-framework-form">
                    @csrf
                    <input type="hidden" name="form_context" value="create-framework-modal">
                    @include('admin.stacking-framework-fields', ['framework' => null, 'formId' => 'create-framework-form'])
                    <div class="actions">
                        <button class="framework-button primary" type="submit">Save draft</button>
                        <button class="framework-button secondary" type="button" data-close-framework-modal>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit/view modals --}}
    @foreach($frameworks as $framework)
        <div class="framework-modal" id="edit-framework-modal-{{ $framework->id }}" aria-hidden="true">
            <div class="framework-modal-card" role="dialog" aria-modal="true" aria-labelledby="edit-framework-modal-title-{{ $framework->id }}">
                <div class="framework-modal-head">
                    <h2 id="edit-framework-modal-title-{{ $framework->id }}">{{ $framework->status === 'approved' ? 'Active framework' : ($framework->status === 'inactive' ? ($framework->newerVersions->isEmpty() ? 'Create a new version' : 'Archived framework version') : 'Edit framework draft') }}</h2>
                    <button type="button" class="framework-modal-close" data-close-framework-modal aria-label="Close">&times;</button>
                </div>
                <div class="framework-modal-body">
                    <form method="POST" action="{{ route('admin.stacking-frameworks.update', $framework->id) }}" id="framework-form-{{ $framework->id }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="form_context" value="edit-framework-modal-{{ $framework->id }}">
                        <fieldset @disabled($framework->status === 'approved' || ($framework->status === 'inactive' && $framework->newerVersions->isNotEmpty())) class="adm-inline-admin-stacking-frameworks-1">
                            @include('admin.stacking-framework-fields', ['framework' => $framework, 'formId' => 'framework-'.$framework->id])
                        </fieldset>

                        <div class="actions">
                            @if($framework->status !== 'approved' && ! ($framework->status === 'inactive' && $framework->newerVersions->isNotEmpty()))
                                <button class="framework-button primary" type="submit">{{ $framework->status === 'inactive' ? 'Create new draft version' : 'Save draft' }}</button>
                            @endif
                            @if(in_array($framework->status, ['draft', 'pending_approval', 'inactive'], true) && ! ($framework->status === 'inactive' && $framework->newerVersions->isNotEmpty()))
                                <button class="framework-button primary" type="submit" form="activate-framework-form-{{ $framework->id }}">Activate</button>
                            @endif
                            @if($framework->status === 'approved')
                                <button class="framework-button warning" type="submit" form="deactivate-framework-form-{{ $framework->id }}">Deactivate</button>
                            @endif
                            <button class="framework-button secondary" type="button" data-close-framework-modal>Close</button>
                        </div>
                    </form>

                    @if(in_array($framework->status, ['draft', 'pending_approval', 'inactive'], true))
                        <form method="POST" action="{{ route('admin.stacking-frameworks.approve', $framework->id) }}" id="activate-framework-form-{{ $framework->id }}" hidden>
                            @csrf
                            <input type="hidden" name="form_context" value="edit-framework-modal-{{ $framework->id }}">
                        </form>
                    @endif

                    @if($framework->status === 'approved')
                        <form method="POST" action="{{ route('admin.stacking-frameworks.deactivate', $framework->id) }}" id="deactivate-framework-form-{{ $framework->id }}" hidden>
                            @csrf
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <script>
        (function () {
            function openModal(id) {
                var modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeModal(modal) {
                if (!modal) return;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                if (!document.querySelector('.framework-modal.is-open')) document.body.style.overflow = '';
            }

            document.addEventListener('click', function (event) {
                var openButton = event.target.closest('[data-open-framework-modal]');
                if (openButton) {
                    openModal(openButton.getAttribute('data-open-framework-modal'));
                    return;
                }

                var closeButton = event.target.closest('[data-close-framework-modal]');
                if (closeButton) {
                    closeModal(closeButton.closest('.framework-modal'));
                    return;
                }

                if (event.target.classList.contains('framework-modal')) closeModal(event.target);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') return;
                var modal = document.querySelector('.framework-modal.is-open');
                if (modal) closeModal(modal);
            });

            @if($errors->any())
                openModal(@js(old('form_context', 'create-framework-modal')));
            @endif
        })();

        function filterRequiredCourses(formId, search) {
            var picker = document.getElementById('course-picker-' + formId);
            if (!picker) return;
            var term = String(search || '').trim().toLowerCase();
            Array.from(picker.options).forEach(function (option) {
                if (!option.value) return;
                option.hidden = term !== '' && !String(option.dataset.search || option.text).toLowerCase().includes(term);
            });
        }

        function addRequiredCourse(formId) {
            var picker = document.getElementById('course-picker-' + formId);
            var container = document.getElementById('requirements-' + formId);
            if (!picker || !container || !picker.value) return;

            var option = picker.options[picker.selectedIndex];
            if (container.querySelector('[data-course-id="' + picker.value + '"]')) return;

            var row = document.createElement('div');
            row.className = 'requirement-row';
            row.dataset.courseId = picker.value;
            var title = option.dataset.title || option.text;
            var details = [option.dataset.category || 'Microcredential'];
            if (option.dataset.pqf) details.push('PQF ' + option.dataset.pqf);
            if (option.dataset.hours) details.push(option.dataset.hours + ' hours');
            row.innerHTML =
                '<input type="hidden" data-course-field value="' + picker.value + '">' +
                '<input type="hidden" data-order-field value="1" data-requirement-order>' +
                '<div class="requirement-course-details"><strong></strong><span></span></div>' +
                '<span class="required-tag">Required</span>' +
                '<button class="remove-row" type="button" aria-label="Remove microcredential">×</button>';
            row.querySelector('strong').textContent = title;
            row.querySelector('.requirement-course-details span').textContent = details.join(' · ');
            row.querySelector('[data-course-field]').name = 'requirements[' + container.children.length + '][course_id]';
            row.querySelector('[data-order-field]').name = 'requirements[' + container.children.length + '][order]';
            row.querySelector('.remove-row').addEventListener('click', function () {
                removeRequiredCourse(this, formId);
            });
            container.appendChild(row);
            picker.value = '';
            updateRequiredCourses(formId);
        }

        function removeRequiredCourse(button, formId) {
            button.closest('.requirement-row').remove();
            updateRequiredCourses(formId);
        }

        function updateRequiredCourses(formId) {
            var container = document.getElementById('requirements-' + formId);
            var picker = document.getElementById('course-picker-' + formId);
            if (!container) return;
            var rows = Array.from(container.querySelectorAll('.requirement-row'));
            var selected = new Set(rows.map(function (row) { return row.dataset.courseId; }));
            rows.forEach(function (row, index) {
                var courseField = row.querySelector('[data-course-field]') || row.querySelector('input[name$="[course_id]"]');
                var orderField = row.querySelector('[data-order-field]') || row.querySelector('[data-requirement-order]');
                if (courseField) courseField.name = 'requirements[' + index + '][course_id]';
                if (orderField) {
                    orderField.name = 'requirements[' + index + '][order]';
                    orderField.value = String(index + 1);
                }
            });
            if (picker) {
                Array.from(picker.options).forEach(function (option) {
                    option.disabled = option.value !== '' && selected.has(option.value);
                });
            }
            var empty = container.querySelector('[data-requirements-empty]');
            if (empty) empty.hidden = rows.length > 0;
            var count = document.getElementById('requirement-count-' + formId);
            if (count) count.textContent = rows.length + ' selected · all required';
        }

        function addOutcomeField(formId) {
            var container = document.getElementById('outcomes-' + formId);
            var template = document.getElementById('outcome-template-' + formId);
            if (!container || !template) return;
            container.appendChild(template.content.cloneNode(true));
            updateOutcomeFields(formId);
        }

        function removeOutcomeField(button, formId) {
            var container = document.getElementById('outcomes-' + formId);
            var rows = container ? container.querySelectorAll('.outcome-field-row') : [];
            if (rows.length === 1) {
                rows[0].querySelector('textarea').value = '';
            } else {
                button.closest('.outcome-field-row').remove();
            }
            updateOutcomeFields(formId);
        }

        function updateOutcomeFields(formId) {
            var container = document.getElementById('outcomes-' + formId);
            if (!container) return;
            Array.from(container.querySelectorAll('.outcome-field-row')).forEach(function (row, index) {
                var field = row.querySelector('textarea');
                var label = row.querySelector('label');
                var id = 'outcome-' + formId + '-' + index;
                field.id = id;
                field.name = 'cumulative_outcomes[' + index + ']';
                if (label) {
                    label.htmlFor = id;
                    label.textContent = 'Learning outcome ' + (index + 1);
                }
            });
        }

        document.addEventListener('change', function (event) {
            if (!event.target.matches('[data-recognition-target-type]')) return;
            var form = event.target.closest('form');
            var isCourseRecognition = ['course_requirement', 'elective_requirement', 'course_equivalency'].includes(event.target.value);
            form.querySelectorAll('[data-credit-field]').forEach(function (field) {
                field.hidden = !isCourseRecognition;
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[id^="requirements-"]').forEach(function (container) {
                updateRequiredCourses(container.id.replace('requirements-', ''));
            });
            document.querySelectorAll('[id^="outcomes-"]').forEach(function (container) {
                updateOutcomeFields(container.id.replace('outcomes-', ''));
            });
        });
    </script>
</x-admin.layout>
