@php
    $formContext = $framework ? 'edit-framework-modal-'.$framework->id : 'create-framework-modal';
    $restoreOldInput = old('form_context') === $formContext;
    $recognitionTargetType = $restoreOldInput
        ? old('recognition_target_type')
        : ($framework?->recognition_target_type ?? '');
    $outcomes = $restoreOldInput
        ? old('cumulative_outcomes', [])
        : ($framework?->cumulative_outcomes ?? []);
    $outcomes = is_array($outcomes) ? array_values($outcomes) : [];
    if ($outcomes === []) {
        $outcomes = [''];
    }
    $selectedRequirements = $restoreOldInput
        ? old('requirements', [])
        : ($framework?->requirements ?? []);
    $selectedRequirements = is_array($selectedRequirements) || $selectedRequirements instanceof \Illuminate\Support\Collection
        ? $selectedRequirements
        : [];
    $courseRecognitionTypes = ['course_requirement', 'elective_requirement', 'course_equivalency'];
    $legacyRecognitionTypes = ['degree_requirement', 'other_institutional_recognition'];
@endphp

<div class="framework-form-sections">
    <section class="framework-form-section">
        <div class="framework-form-section-heading">
            <span>01</span>
            <div><h3>Framework information</h3><p>Identify the approved combination and its purpose.</p></div>
        </div>
        <div class="form-grid">
            <div class="field field-wide">
                <label for="name-{{ $formId }}">Framework name</label>
                <input id="name-{{ $formId }}" name="name" value="{{ $restoreOldInput ? old('name') : $framework?->name }}" required maxlength="150">
                @error('name')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field field-wide">
                <label for="description-{{ $formId }}">Description</label>
                <textarea id="description-{{ $formId }}" name="description" rows="3" maxlength="2000" required>{{ $restoreOldInput ? old('description') : $framework?->description }}</textarea>
                @error('description')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="pqf-{{ $formId }}">PQF level</label>
                <select id="pqf-{{ $formId }}" name="pqf_level">
                    <option value="">Select level (optional)</option>
                    @for($level = 5; $level <= 8; $level++)
                        <option value="{{ $level }}" @selected((string) ($restoreOldInput ? old('pqf_level') : $framework?->pqf_level) === (string) $level)>Level {{ $level }}</option>
                    @endfor
                </select>
                @error('pqf_level')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </section>

    <section class="framework-form-section">
        <div class="framework-form-section-heading">
            <span>02</span>
            <div><h3>Recognition details</h3><p>Describe what the complete set of microcredentials is intended to satisfy.</p></div>
        </div>
        <div class="form-grid">
            <div class="field">
                <label for="recognition-type-{{ $formId }}">Recognition target type</label>
                <select id="recognition-type-{{ $formId }}" name="recognition_target_type" data-recognition-target-type required>
                    <option value="">Select target type</option>
                    <option value="course_requirement" @selected($recognitionTargetType === 'course_requirement')>Course requirement</option>
                    <option value="elective_requirement" @selected($recognitionTargetType === 'elective_requirement')>Elective requirement</option>
                    <option value="specialization" @selected($recognitionTargetType === 'specialization')>Specialization</option>
                    <option value="certificate" @selected($recognitionTargetType === 'certificate')>Certificate</option>
                    <option value="other_institutional_requirement" @selected($recognitionTargetType === 'other_institutional_requirement')>Other institutional requirement</option>
                    <option value="other" @selected($recognitionTargetType === 'other')>Other</option>
                    @if(in_array($recognitionTargetType, $legacyRecognitionTypes, true))
                        <optgroup label="Existing framework target">
                            <option value="degree_requirement" @selected($recognitionTargetType === 'degree_requirement')>Degree requirement</option>
                            <option value="other_institutional_recognition" @selected($recognitionTargetType === 'other_institutional_recognition')>Other institutional recognition</option>
                        </optgroup>
                    @endif
                    @if($recognitionTargetType === 'course_equivalency')
                        <optgroup label="Existing framework target">
                            <option value="course_equivalency" selected>Course equivalency</option>
                        </optgroup>
                    @endif
                </select>
                @error('recognition_target_type')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="target-{{ $formId }}">Intended recognition</label>
                <input id="target-{{ $formId }}" name="target_recognition" value="{{ $restoreOldInput ? old('target_recognition') : $framework?->target_recognition }}" required maxlength="255" placeholder="e.g. Information Technology elective requirement">
                @error('target_recognition')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="approving-unit-{{ $formId }}">Approving academic unit</label>
                <input id="approving-unit-{{ $formId }}" name="approving_academic_unit" value="{{ $restoreOldInput ? old('approving_academic_unit') : $framework?->approving_academic_unit }}" required maxlength="255">
                @error('approving_academic_unit')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field" data-credit-field @if(! in_array($recognitionTargetType, $courseRecognitionTypes, true)) hidden @endif>
                <label for="equivalent-course-{{ $formId }}">Equivalent course (if applicable)</label>
                <input id="equivalent-course-{{ $formId }}" name="equivalent_course" value="{{ $restoreOldInput ? old('equivalent_course') : $framework?->equivalent_course }}" maxlength="255">
            </div>
            <div class="field" data-credit-field @if(! in_array($recognitionTargetType, $courseRecognitionTypes, true)) hidden @endif>
                <label for="equivalent-units-{{ $formId }}">Equivalent academic units (if applicable)</label>
                <input id="equivalent-units-{{ $formId }}" name="equivalent_units" type="number" min="0" step="0.01" value="{{ $restoreOldInput ? old('equivalent_units') : $framework?->equivalent_units }}">
                @error('equivalent_units')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field field-wide">
                <label for="conditions-{{ $formId }}">Recognition conditions (optional)</label>
                <textarea id="conditions-{{ $formId }}" name="credit_recognition_conditions" rows="2" maxlength="2000">{{ $restoreOldInput ? old('credit_recognition_conditions') : $framework?->credit_recognition_conditions }}</textarea>
            </div>
        </div>
    </section>

    <section class="framework-form-section">
        <div class="framework-form-section-heading">
            <span>03</span>
            <div><h3>Cumulative learning outcomes</h3><p>Add one outcome per field. These describe the combined competencies demonstrated by the full framework.</p></div>
        </div>
        <div id="outcomes-{{ $formId }}" class="framework-outcomes">
            @foreach($outcomes as $index => $outcome)
                <div class="outcome-field-row">
                    <label class="sr-only" for="outcome-{{ $formId }}-{{ $index }}">Learning outcome {{ $index + 1 }}</label>
                    <textarea id="outcome-{{ $formId }}-{{ $index }}" name="cumulative_outcomes[{{ $index }}]" rows="2" maxlength="500" required placeholder="Enter one measurable learning outcome">{{ $outcome }}</textarea>
                    <button class="remove-row" type="button" aria-label="Remove learning outcome" onclick="removeOutcomeField(this, '{{ $formId }}')">×</button>
                </div>
            @endforeach
        </div>
        <button class="framework-button secondary" type="button" onclick="addOutcomeField('{{ $formId }}')">+ Add learning outcome</button>
        @error('cumulative_outcomes')<span class="field-error">{{ $message }}</span>@enderror
        @foreach($errors->getMessages() as $key => $messages)
            @if(str_starts_with($key, 'cumulative_outcomes.'))
                <span class="field-error">{{ $messages[0] }}</span>
                @break
            @endif
        @endforeach
        <template id="outcome-template-{{ $formId }}">
            <div class="outcome-field-row">
                <label class="sr-only" data-outcome-label>Learning outcome</label>
                <textarea data-outcome-field rows="2" maxlength="500" required placeholder="Enter one measurable learning outcome"></textarea>
                <button class="remove-row" type="button" aria-label="Remove learning outcome" onclick="removeOutcomeField(this, '{{ $formId }}')">×</button>
            </div>
        </template>
    </section>

    <section class="framework-form-section">
        <div class="framework-form-section-heading">
            <span>04</span>
            <div><h3>Required microcredentials</h3><p>Every selected microcredential is required. Learners must officially complete all of them to meet this framework.</p></div>
        </div>
        <div class="course-picker">
            <div class="field">
                <label for="course-search-{{ $formId }}">Search available microcredentials</label>
                <input id="course-search-{{ $formId }}" type="search" autocomplete="off" placeholder="Search title, category, or PQF level" oninput="filterRequiredCourses('{{ $formId }}', this.value)">
            </div>
            <div class="course-picker-controls">
                <div class="field">
                    <label for="course-picker-{{ $formId }}">Available approved courses</label>
                    <select id="course-picker-{{ $formId }}" data-course-picker>
                        <option value="">Select a microcredential</option>
                        @foreach($courses as $course)
                            <option
                                value="{{ $course->id }}"
                                data-title="{{ $course->title }}"
                                data-category="{{ $course->category }}"
                                data-pqf="{{ $course->pqf_level }}"
                                data-hours="{{ $course->learning_hours }}"
                                data-search="{{ strtolower($course->title.' '.$course->category.' '.$course->pqf_level) }}"
                            >{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="framework-button secondary" type="button" onclick="addRequiredCourse('{{ $formId }}')">Add required microcredential</button>
            </div>
            @if($courses->isEmpty())
                <p class="picker-empty">No published, approved microcredential courses are available.</p>
            @endif
            @error('requirements')<span class="field-error">{{ $message }}</span>@enderror
            @foreach($errors->getMessages() as $key => $messages)
                @if(str_starts_with($key, 'requirements.'))
                    <span class="field-error">{{ $messages[0] }}</span>
                    @break
                @endif
            @endforeach
        </div>

        <div class="selected-requirements-header">
            <strong>Selected required microcredentials</strong>
            <span id="requirement-count-{{ $formId }}" aria-live="polite"></span>
        </div>
        <div id="requirements-{{ $formId }}" class="selected-requirements" data-next-index="{{ count($selectedRequirements) }}">
            @foreach($selectedRequirements as $index => $selectedRequirement)
                @php
                    $courseId = (int) (is_array($selectedRequirement) ? ($selectedRequirement['course_id'] ?? 0) : $selectedRequirement->course_id);
                    $courseOrder = (int) (is_array($selectedRequirement) ? ($selectedRequirement['order'] ?? $index + 1) : $selectedRequirement->order);
                    $selectedCourse = $courses->firstWhere('id', $courseId);
                @endphp
                <div class="requirement-row" data-course-id="{{ $courseId }}">
                    <input type="hidden" name="requirements[{{ $index }}][course_id]" value="{{ $courseId }}">
                    <input type="hidden" name="requirements[{{ $index }}][order]" value="{{ $courseOrder }}" data-requirement-order>
                    <div class="requirement-course-details">
                        <strong>{{ $selectedCourse?->title ?? 'Removed course' }}</strong>
                        <span>{{ $selectedCourse?->category ?: 'Microcredential' }}{{ $selectedCourse?->pqf_level ? ' · PQF '.$selectedCourse->pqf_level : '' }}{{ $selectedCourse?->learning_hours ? ' · '.$selectedCourse->learning_hours.' hours' : '' }}</span>
                    </div>
                    <span class="required-tag">Required</span>
                    <button class="remove-row" type="button" aria-label="Remove microcredential" onclick="removeRequiredCourse(this, '{{ $formId }}')">×</button>
                </div>
            @endforeach
            <p class="picker-empty" data-requirements-empty @if(count($selectedRequirements) > 0) hidden @endif>No microcredentials selected. Add at least one.</p>
        </div>

        <label class="sequence-option">
            <input type="checkbox" name="sequence_required" value="1" @checked((bool) ($restoreOldInput ? old('sequence_required') : $framework?->sequence_required))>
            Require the displayed completion order
        </label>
        <small class="field-help">Enable only when the institutional approval documentation explicitly requires a fixed sequence.</small>
        @error('sequence_required')<span class="field-error">{{ $message }}</span>@enderror
    </section>

    <section class="framework-form-section">
        <div class="framework-form-section-heading">
            <span>05</span>
            <div><h3>Approval and effective period</h3><p>Record the institutional approval source. Required before an administrator can activate this draft.</p></div>
        </div>
        <div class="form-grid">
            <div class="field field-wide">
                <label for="approval-reference-{{ $formId }}">Approval reference</label>
                <input id="approval-reference-{{ $formId }}" name="approval_reference" value="{{ $restoreOldInput ? old('approval_reference') : $framework?->approval_reference }}" maxlength="255" placeholder="Resolution, memo, or approval identifier">
                @error('approval_reference')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="approval-date-{{ $formId }}">Approval date</label>
                <input id="approval-date-{{ $formId }}" name="approval_date" type="date" value="{{ $restoreOldInput ? old('approval_date') : $framework?->approval_date?->format('Y-m-d') }}">
                @error('approval_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="effective-date-{{ $formId }}">Effective date</label>
                <input id="effective-date-{{ $formId }}" name="effective_date" type="date" value="{{ $restoreOldInput ? old('effective_date') : $framework?->effective_date?->format('Y-m-d') }}">
                @error('effective_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="expiration-date-{{ $formId }}">Expiration date (optional)</label>
                <input id="expiration-date-{{ $formId }}" name="expiration_date" type="date" value="{{ $restoreOldInput ? old('expiration_date') : $framework?->expiration_date?->format('Y-m-d') }}">
                @error('expiration_date')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </section>
</div>
