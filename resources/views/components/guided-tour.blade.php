@props([
    'tourId' => 'guided-tour',
    'label' => 'Guided tour',
    'steps' => [],
    'launcherLabel' => null,
    'autoStart' => false,
    'formAction' => null,
    'finishLabel' => 'Done',
    'skipLabel' => 'Skip guide',
    'startAction' => 'start',
    'skipAction' => 'dismiss',
])

@if (count($steps) > 0)
    <style>
        .guided-tour-spotlight {
            position: fixed;
            z-index: 5000;
            border-radius: 12px;
            pointer-events: none;
            box-shadow: 0 0 0 3px #fff, 0 0 0 7px #0927d8, 0 0 0 100vmax rgba(7, 20, 63, .68);
            transition: top .2s ease, left .2s ease, width .2s ease, height .2s ease;
        }
        .guided-tour-spotlight[hidden] { display: none; }
        .guided-tour-dialog {
            position: fixed;
            z-index: 5001;
            width: min(360px, calc(100vw - 32px));
            max-height: calc(100vh - 32px);
            overflow-y: auto;
            padding: 20px;
            border: 1px solid #e3e8f3;
            border-radius: 16px;
            background: #fff;
            color: #111a46;
            box-shadow: 0 16px 48px rgba(4, 12, 45, .28);
            font-family: Inter, 'Segoe UI', sans-serif;
        }
        .guided-tour-dialog--compact { padding: 14px; }
        .guided-tour-dialog--compact .guided-tour__count { margin: 10px 0 4px; }
        .guided-tour-dialog--compact h2 { margin-bottom: 5px; }
        .guided-tour-dialog--compact .guided-tour__description { font-size: .82rem; line-height: 1.4; }
        .guided-tour-dialog--compact .guided-tour__progress { margin-top: 10px; }
        .guided-tour-dialog--compact .guided-tour__footer { margin-top: 10px; }
        .guided-tour-dialog--compact .guided-tour__next,
        .guided-tour-dialog--compact .guided-tour__finish { min-height: 34px; }
        .guided-tour-dialog[hidden] { display: none !important; }
        .guided-tour-launch {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            min-height: 38px;
            height: 38px;
            padding: 0;
            border: 1px solid #ccd5ef;
            border-radius: 50%;
            background: #fff;
            color: #0927d8;
            font: inherit;
            font-size: .82rem;
            font-weight: 800;
            cursor: pointer;
            flex: 0 0 auto;
        }
        .guided-tour-launch:hover {
            border-color: #e8b211;
            background: #fff9e6;
            color: #a56b00;
        }
        .guided-tour-launch svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }
        .guided-tour-dialog [hidden] { display: none !important; }
        .guided-tour__top,
        .guided-tour__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .guided-tour__eyebrow {
            color: #0927d8;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .guided-tour__skip,
        .guided-tour__previous {
            padding: 7px;
            border: 0;
            background: transparent;
            color: #5a6a8a;
            font: inherit;
            font-size: .78rem;
            font-weight: 700;
            cursor: pointer;
        }
        .guided-tour__count {
            margin: 14px 0 5px;
            color: #687694;
            font-size: .72rem;
            font-weight: 700;
        }
        .guided-tour-dialog h2 {
            margin: 0 0 7px;
            color: #071550;
            font-size: 1.13rem;
            line-height: 1.3;
        }
        .guided-tour__description {
            margin: 0;
            color: #4b5872;
            font-size: .86rem;
            line-height: 1.55;
        }
        .guided-tour__progress {
            height: 4px;
            margin-top: 15px;
            overflow: hidden;
            border-radius: 999px;
            background: #e8edf7;
        }
        .guided-tour__progress-fill {
            width: 25%;
            height: 100%;
            border-radius: inherit;
            background: #0927d8;
            transition: width .2s ease;
        }
        .guided-tour__footer { margin-top: 15px; }
        .guided-tour__next,
        .guided-tour__finish {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 15px;
            border: 0;
            border-radius: 9px;
            background: #0927d8;
            color: #fff;
            font: inherit;
            font-size: .82rem;
            font-weight: 800;
            cursor: pointer;
        }
        .guided-tour__next:hover,
        .guided-tour__finish:hover { background: #071550; }
        .guided-tour-dialog button:focus-visible,
        .guided-tour-launch:focus-visible {
            outline: 3px solid rgba(9, 39, 216, .35);
            outline-offset: 3px;
        }
        @media (max-width: 640px) {
            .guided-tour-dialog { width: min(320px, calc(100vw - 32px)); padding: 14px; }
            .guided-tour__count { margin: 10px 0 4px; }
            .guided-tour-dialog h2 { margin-bottom: 5px; font-size: 1.05rem; }
            .guided-tour__description { font-size: .8rem; line-height: 1.42; }
            .guided-tour__progress { margin-top: 10px; }
            .guided-tour__footer { margin-top: 10px; }
            .guided-tour__next,
            .guided-tour__finish { min-height: 34px; padding: 0 12px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .guided-tour-spotlight,
            .guided-tour__progress-fill { transition: none; }
        }
    </style>

    @if ($launcherLabel)
        <button class="guided-tour-launch" id="{{ $tourId }}-launch" type="button" aria-label="{{ $launcherLabel }}" title="{{ $launcherLabel }}" aria-haspopup="dialog" aria-controls="{{ $tourId }}-dialog" aria-expanded="false">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6m-5 3h4m-5-7.5a6 6 0 1 1 6 0c-.8.6-1 1.4-1 2.5h-4c0-1.1-.2-1.9-1-2.5Z"/></svg>
        </button>
    @endif

    <div class="guided-tour-spotlight" id="{{ $tourId }}-spotlight" aria-hidden="true" hidden></div>
    <section
        class="guided-tour-dialog"
        id="{{ $tourId }}-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $tourId }}-title-0"
        aria-describedby="{{ $tourId }}-description-0"
        @unless ($autoStart) hidden @endunless
    >
        @if ($formAction)
            <form method="POST" action="{{ $formAction }}">
                @csrf
        @else
            <div>
        @endif
            <div class="guided-tour__top">
                <span class="guided-tour__eyebrow">{{ $label }}</span>
                @if ($formAction)
                    <button class="guided-tour__skip" id="{{ $tourId }}-skip" type="submit" name="action" value="{{ $skipAction }}">{{ $skipLabel }}</button>
                @else
                    <button class="guided-tour__skip" id="{{ $tourId }}-skip" type="button" data-tour-dismiss>{{ $skipLabel }}</button>
                @endif
            </div>
            <p class="guided-tour__count" aria-live="polite">
                Step <span id="{{ $tourId }}-current">1</span> of {{ count($steps) }}
            </p>
            <div id="{{ $tourId }}-steps">
                @foreach ($steps as $step)
                    <article
                        data-guided-tour-step
                        data-tour-step-target="{{ $step['target'] }}"
                        @if (! empty($step['fallback'])) data-tour-fallback="{{ $step['fallback'] }}" @endif
                        @if (! empty($step['compact'])) data-tour-compact @endif
                        @if (! empty($step['placement'])) data-tour-placement="{{ $step['placement'] }}" @endif
                        @if (! empty($step['prepare'])) data-tour-prepare="{{ $step['prepare'] }}" @endif
                        @if (! empty($step['allow_click'])) data-tour-allow-click @endif
                        @if (! $loop->first) hidden @endif
                    >
                        <h2 id="{{ $tourId }}-title-{{ $loop->index }}">{{ $step['title'] }}</h2>
                        <p class="guided-tour__description" id="{{ $tourId }}-description-{{ $loop->index }}">{{ $step['description'] }}</p>
                    </article>
                @endforeach
            </div>
            <div class="guided-tour__progress" role="progressbar" aria-label="Guided tour progress" aria-valuemin="1" aria-valuemax="{{ count($steps) }}" aria-valuenow="1">
                <div class="guided-tour__progress-fill" id="{{ $tourId }}-progress"></div>
            </div>
            <div class="guided-tour__footer">
                <button class="guided-tour__previous" id="{{ $tourId }}-previous" type="button" hidden>Back</button>
                <button class="guided-tour__next" id="{{ $tourId }}-next" type="button">Next</button>
                @if ($formAction)
                    <button class="guided-tour__finish" id="{{ $tourId }}-finish" type="submit" name="action" value="{{ $startAction }}" hidden>{{ $finishLabel }}</button>
                @else
                    <button class="guided-tour__finish" id="{{ $tourId }}-finish" type="button" data-tour-dismiss hidden>{{ $finishLabel }}</button>
                @endif
            </div>
        @if ($formAction)
            </form>
        @else
            </div>
        @endif
    </section>

    <script>
        (function () {
            var tourId = @json($tourId);
            var dialog = document.getElementById(tourId + '-dialog');
            var spotlight = document.getElementById(tourId + '-spotlight');
            var launcher = document.getElementById(tourId + '-launch');
            var steps = dialog ? Array.from(dialog.querySelectorAll('[data-guided-tour-step]')) : [];
            var currentLabel = document.getElementById(tourId + '-current');
            var progressBar = dialog ? dialog.querySelector('.guided-tour__progress') : null;
            var progressFill = document.getElementById(tourId + '-progress');
            var previousButton = document.getElementById(tourId + '-previous');
            var nextButton = document.getElementById(tourId + '-next');
            var finishButton = document.getElementById(tourId + '-finish');
            var skipButton = document.getElementById(tourId + '-skip');
            var programmaticClick = false;
            if (!dialog || !spotlight || !steps.length) return;

            var activeStep = 0;
            var activeTarget = null;

            function isVisible(target) {
                if (!target || !target.getClientRects().length) return false;
                for (var element = target; element && element !== document.documentElement; element = element.parentElement) {
                    var style = window.getComputedStyle(element);
                    if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
                }
                var rect = target.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0;
            }

            function findTarget(step) {
                var target = document.querySelector('[data-tour-target="' + step.dataset.tourStepTarget + '"]');
                if (!isVisible(target) && step.dataset.tourFallback) {
                    target = document.querySelector('[data-tour-target="' + step.dataset.tourFallback + '"]');
                }

                return isVisible(target) ? target : null;
            }

            function positionTour() {
                if (dialog.hidden) {
                    spotlight.hidden = true;
                    return;
                }
                if (!activeTarget) {
                    spotlight.hidden = true;
                    dialog.style.top = '50%';
                    dialog.style.left = '50%';
                    dialog.style.transform = 'translate(-50%, -50%)';
                    dialog.style.visibility = 'visible';
                    return;
                }

                var rect = activeTarget.getBoundingClientRect();
                var padding = 7;
                spotlight.hidden = false;
                spotlight.style.top = (rect.top - padding) + 'px';
                spotlight.style.left = (rect.left - padding) + 'px';
                spotlight.style.width = (rect.width + padding * 2) + 'px';
                spotlight.style.height = (rect.height + padding * 2) + 'px';

                dialog.style.transform = 'none';
                dialog.style.visibility = 'hidden';
                var dialogRect = dialog.getBoundingClientRect();
                var margin = 16;
                var gap = 18;
                var top;
                var left;
                var placement = steps[activeStep].dataset.tourPlacement;
                if (placement === 'viewport-bottom-right') {
                    top = window.innerHeight - dialogRect.height - margin;
                    left = window.innerWidth - dialogRect.width - margin;
                } else {
                    top = rect.bottom + gap;
                    if (top + dialogRect.height > window.innerHeight - margin) {
                        top = rect.top - dialogRect.height - gap;
                    }
                    left = rect.left;
                }
                top = Math.max(margin, Math.min(top, window.innerHeight - dialogRect.height - margin));
                left = Math.max(margin, Math.min(left, window.innerWidth - dialogRect.width - margin));
                dialog.style.top = top + 'px';
                dialog.style.left = left + 'px';
                dialog.style.visibility = 'visible';
            }

            function renderStep(shouldScroll) {
                var step = steps[activeStep];
                var heading = step.querySelector('h2');
                var description = step.querySelector('.guided-tour__description');
                dialog.classList.toggle('guided-tour-dialog--compact', step.hasAttribute('data-tour-compact'));
                steps.forEach(function (item, index) {
                    item.hidden = index !== activeStep;
                });
                dialog.setAttribute('aria-labelledby', heading.id);
                dialog.setAttribute('aria-describedby', description.id);
                currentLabel.textContent = String(activeStep + 1);
                progressBar.setAttribute('aria-valuenow', String(activeStep + 1));
                progressFill.style.width = ((activeStep + 1) / steps.length * 100) + '%';
                previousButton.hidden = activeStep === 0;
                nextButton.hidden = activeStep === steps.length - 1;
                finishButton.hidden = activeStep !== steps.length - 1;

                if (step.dataset.tourPrepare) {
                    var prepareTarget = document.querySelector(step.dataset.tourPrepare);
                    if (prepareTarget && prepareTarget.getAttribute('aria-expanded') !== 'true') {
                        programmaticClick = true;
                        prepareTarget.click();
                        programmaticClick = false;
                    }
                }

                activeTarget = findTarget(step);
                if (activeTarget && shouldScroll) {
                    activeTarget.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                }
                window.requestAnimationFrame(positionTour);
                if (activeTarget && shouldScroll) {
                    window.setTimeout(positionTour, 350);
                }
            }

            function openTour() {
                activeStep = 0;
                dialog.hidden = false;
                if (launcher) launcher.setAttribute('aria-expanded', 'true');
                renderStep(true);
                nextButton.focus();
            }

            function closeTour() {
                dialog.hidden = true;
                spotlight.hidden = true;
                if (launcher) launcher.setAttribute('aria-expanded', 'false');
                if (launcher) launcher.focus();
            }

            if (launcher) {
                launcher.addEventListener('click', openTour);
            }
            dialog.querySelectorAll('[data-tour-dismiss]').forEach(function (button) {
                button.addEventListener('click', closeTour);
            });
            previousButton.addEventListener('click', function () {
                activeStep = Math.max(0, activeStep - 1);
                renderStep(true);
                previousButton.focus();
            });
            nextButton.addEventListener('click', function () {
                activeStep = Math.min(steps.length - 1, activeStep + 1);
                renderStep(true);
                (activeStep === steps.length - 1 ? finishButton : nextButton).focus();
            });
            window.addEventListener('resize', positionTour);
            window.addEventListener('scroll', positionTour, true);
            document.addEventListener('click', function (event) {
                if (dialog.hidden || programmaticClick || dialog.contains(event.target)) return;

                if (
                    activeTarget
                    && activeTarget.contains(event.target)
                    && activeStep === steps.length - 1
                    && steps[activeStep].hasAttribute('data-tour-allow-click')
                    && ! dialog.querySelector('form')
                ) {
                    closeTour();
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                if (!activeTarget || !activeTarget.contains(event.target)) return;

                if (activeStep === steps.length - 1) {
                    finishButton.click();
                } else {
                    nextButton.click();
                }
            }, true);
            document.addEventListener('keydown', function (event) {
                if (dialog.hidden) return;
                if (event.key === 'Escape') {
                    event.preventDefault();
                    skipButton.click();
                    return;
                }
                if (event.key !== 'Tab') return;

                var focusable = Array.from(dialog.querySelectorAll('button:not([hidden]):not(:disabled)'));
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (! event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            });

            if (@json($autoStart)) {
                openTour();
            }
        })();
    </script>
@endif
