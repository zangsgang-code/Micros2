{{--
    resources/views/my_badges.blade.php

    Expected data from the controller, e.g.:

    return view('student.badges', [
        'user'  => $user,                  // Auth::user() - needs ->name
        'badges' => $recentlyEarnedBadges,  // collection, most recent first
    ]);

    Each $badge is expected to expose:
        ->name, ->description, ->icon_url, ->earned_at (a Carbon instance, for ->diffForHumans())
--}}
<x-layout title="My Badges | Upskill" shell="student" page="badges">
        <div class="page-head page-header ui-page-header student-page-heading">
            <div class="ui-page-header__content">
                <h2 class="ui-page-title">My Badge Collection</h2>
                <p class="ui-page-subtitle">Badges earned through course completion</p>
            </div>
        </div>

        {{-- Recently Earned --}}
        <h3 class="ui-section-title">Recently Earned</h3>
        <div class="badge-grid">
            @forelse ($badges as $badge)
                <div class="badge-card" id="badge-{{ $badge->id }}" role="button" tabindex="0" aria-expanded="false"
                        aria-label="Show details for {{ $badge->name }}">
                    <span class="badge-card__inner">
                        <span class="badge-face badge-front ui-card-surface" data-badge-face="front">
                            <span class="badge-time">{{ $badge->earned_at?->diffForHumans() ?? '' }}</span>
                            <span class="badge-icon">
                                @if($badge->icon_url)
                                    <img src="{{ $badge->icon_url }}" alt="" loading="lazy">
                                @else
                                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <path d="M12 3l2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3z"/>
                                    </svg>
                                @endif
                            </span>
                            <span class="badge-title">{{ $badge->name }}</span>
                            @if($badge->description)
                                <span class="desc">{{ $badge->description }}</span>
                            @endif
                            <span class="badge-earned-pill ui-badge {{ strtolower($badge->status ?? 'active') === 'revoked' ? 'ui-badge--danger' : 'ui-badge--accent' }}">{{ strtolower($badge->status ?? 'active') === 'revoked' ? 'Revoked' : 'Earned' }}</span>
                            <span class="badge-flip-hint">Click or press Enter to view details</span>
                        </span>
                        <span class="badge-face badge-back ui-card-surface" data-badge-face="back" aria-hidden="true">
                            <span class="badge-detail-title">{{ $badge->name }}</span>
                            <dl class="badge-details">
                                <div class="badge-detail">
                                    <dt>Date received</dt>
                                    <dd>{{ $badge->earned_at?->format('F j, Y') ?? 'Date unavailable' }}</dd>
                                </div>
                                @if($badge->pqf_level)
                                    <div class="badge-detail"><dt>PQF level</dt><dd>{{ $badge->pqf_level }}</dd></div>
                                @endif
                                @if($badge->issuing_institution)
                                    <div class="badge-detail"><dt>Issued by</dt><dd>{{ $badge->issuing_institution }}</dd></div>
                                @endif
                                @if($badge->pathway_name)
                                    <div class="badge-detail"><dt>Pathway</dt><dd>{{ $badge->pathway_name }}</dd></div>
                                @endif
                                @if($badge->course_names->isNotEmpty())
                                    <div class="badge-detail"><dt>Related course</dt><dd>{{ $badge->course_names->implode(', ') }}</dd></div>
                                @endif
                                @if(!empty($badge->competencies))
                                    <div class="badge-detail">
                                        <dt>Competencies</dt>
                                        <dd><ul>
                                            @foreach($badge->competencies as $competency)
                                                <li>{{ is_array($competency) ? ($competency['title'] ?? $competency['name'] ?? 'Competency') : $competency }}</li>
                                            @endforeach
                                        </ul></dd>
                                    </div>
                                @endif
                                @if(!empty($badge->learning_outcomes))
                                    <div class="badge-detail">
                                        <dt>Learning outcomes</dt>
                                        <dd>
                                            @if(count($badge->learning_outcomes) === 1 && is_array($badge->learning_outcomes[0]))
                                                <div class="rich-content">{!! \App\Support\RichTextSanitizer::sanitize($badge->learning_outcomes[0]['description'] ?? '') !!}</div>
                                            @else
                                                <ul>
                                                    @foreach($badge->learning_outcomes as $outcome)
                                                        <li>{{ is_array($outcome) ? trim(($outcome['code'] ?? '').' '.($outcome['description'] ?? '')) : $outcome }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                                @if($badge->credential_uid)
                                    <div class="badge-detail"><dt>Credential ID</dt><dd>{{ $badge->credential_uid }}</dd></div>
                                @endif
                                <div class="badge-detail">
                                    <dt>Status</dt>
                                    <dd><span class="badge-status ui-badge {{ strtolower($badge->status ?? 'active') === 'revoked' ? 'ui-badge--danger is-revoked' : 'ui-badge--success' }}">{{ ucfirst($badge->status ?? 'active') }}</span></dd>
                                </div>
                                @if($badge->revoked_at)
                                    <div class="badge-detail"><dt>Revoked on</dt><dd>{{ $badge->revoked_at->format('F j, Y') }}</dd></div>
                                @endif
                                @if($badge->revocation_reason)
                                    <div class="badge-detail"><dt>Revocation reason</dt><dd>{{ $badge->revocation_reason }}</dd></div>
                                @endif
                            </dl>
                            @if($badge->credential_uid && $badge->verification_qr_url && $badge->verification_url)
                                <span class="badge-verification-qr">
                                    <img src="{{ $badge->verification_qr_url }}" alt="QR code to verify {{ $badge->name }}">
                                    <span class="badge-verification-qr__copy">
                                        <span>Scan to verify this badge</span>
                                    </span>
                                </span>
                                <span class="badge-download-actions">
                                    <button class="badge-download-button" type="button" data-badge-download aria-label="Download full {{ $badge->name }} badge image">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v11m0 0 4-4m-4 4-4-4M5 16v4h14v-4"/></svg>
                                        <span>Download full image</span>
                                    </button>
                                    <button class="badge-download-button" type="button" data-badge-download-compact aria-label="Download compact {{ $badge->name }} badge image with QR code">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v11m0 0 4-4m-4 4-4-4M5 16v4h14v-4"/></svg>
                                        <span>Download badge + QR</span>
                                    </button>
                                </span>
                            @endif
                            <span class="badge-flip-hint">Click to return to badge</span>
                        </span>
                    </span>
                </div>
            @empty
                <div class="empty-state ui-empty-state">
                    No badges earned yet. Complete a course to start collecting them.
                </div>
            @endforelse
        </div>

{{-- â”€â”€ Back to top (appears on long pages) â”€â”€ --}}
<button id="back-to-top-btn" type="button" title="Back to top" aria-label="Back to top"
        onclick="window.scrollTo({top:0,behavior:'smooth'});">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
</button>

<script>
    (function () {
        var btn = document.getElementById('back-to-top-btn');
        if (!btn) return;
        function toggleBackToTop() {
            btn.style.display = (window.scrollY || document.documentElement.scrollTop) > 400 ? 'flex' : 'none';
        }
        window.addEventListener('scroll', toggleBackToTop, { passive: true });
        toggleBackToTop();
    })();
</script>

<script>
    document.querySelectorAll('.badge-card').forEach(function (card) {
        var front = card.querySelector('[data-badge-face="front"]');
        var back = card.querySelector('[data-badge-face="back"]');
        var title = card.querySelector('.badge-title').textContent.trim();

        function toggleBadge(event) {
            if (event.target.closest('a, button')) return;

            var flipped = card.classList.toggle('is-flipped');
            card.setAttribute('aria-expanded', flipped ? 'true' : 'false');
            card.setAttribute('aria-label', (flipped ? 'Hide details for ' : 'Show details for ') + title);
            front.setAttribute('aria-hidden', flipped ? 'true' : 'false');
            back.setAttribute('aria-hidden', flipped ? 'false' : 'true');
        }

        card.addEventListener('click', toggleBadge);
        card.addEventListener('keydown', function (event) {
            if (event.target !== card) return;

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleBadge();
            }
        });
    });
</script>

<script>
    (function () {
        function loadBadgeImage(src) {
            if (!src) return Promise.resolve(null);
            return new Promise(function (resolve) {
                var image = new Image();
                if (!src.startsWith('data:') && new URL(src, window.location.href).origin !== window.location.origin) {
                    image.crossOrigin = 'anonymous';
                }
                image.onload = function () { resolve(image); };
                image.onerror = function () { resolve(null); };
                image.src = src;
            });
        }

        function wrapBadgeText(context, text, maxWidth, font) {
            context.font = font;
            var words = String(text || '').replace(/\s+/g, ' ').trim().split(' ').filter(Boolean);
            var lines = [];
            var line = '';

            words.forEach(function (word) {
                var candidate = line ? line + ' ' + word : word;
                if (context.measureText(candidate).width <= maxWidth) {
                    line = candidate;
                    return;
                }
                if (line) lines.push(line);
                line = '';
                if (context.measureText(word).width <= maxWidth) {
                    line = word;
                    return;
                }
                var chunk = '';
                Array.from(word).forEach(function (character) {
                    if (chunk && context.measureText(chunk + character).width > maxWidth) {
                        lines.push(chunk);
                        chunk = character;
                    } else {
                        chunk += character;
                    }
                });
                line = chunk;
            });

            if (line) lines.push(line);
            return lines.length ? lines : [''];
        }

        function drawBadgeText(context, lines, x, y, lineHeight, color, font, align) {
            context.fillStyle = color;
            context.font = font;
            context.textAlign = align || 'left';
            lines.forEach(function (line, index) {
                context.fillText(line, x, y + index * lineHeight);
            });
        }

        function roundRect(context, x, y, width, height, radius) {
            context.beginPath();
            context.roundRect(x, y, width, height, radius);
        }

        async function loadBadgeDownloadData(card) {
            var detailRows = Array.from(card.querySelectorAll('.badge-details .badge-detail'));
            var details = detailRows.map(function (row) {
                var label = row.querySelector('dt')?.textContent.trim() || '';
                return {
                    label: label,
                    value: row.querySelector('dd')?.textContent.replace(/\s+/g, ' ').trim() || '',
                    items: label.toLowerCase() === 'learning outcomes'
                        ? Array.from(row.querySelectorAll('dd > ul > li')).map(function (item) {
                            return item.textContent.replace(/\s+/g, ' ').trim();
                        }).filter(Boolean)
                        : []
                };
            }).filter(function (row) { return row.label && row.value; });
            var dateReceived = detailRows.find(function (row) {
                return row.querySelector('dt')?.textContent.trim() === 'Date received';
            })?.querySelector('dd')?.textContent.replace(/\s+/g, ' ').trim() || '';
            var iconSource = card.querySelector('.badge-icon img')?.src || '';
            var qrSource = card.querySelector('.badge-verification-qr img')?.src || '';
            var images = await Promise.all([loadBadgeImage(iconSource), loadBadgeImage(qrSource)]);
            var qr = images[1];
            if (!qr) throw new Error('The verification QR code could not be loaded. Try again in a moment.');

            return {
                name: card.querySelector('.badge-title')?.textContent.trim() || 'Badge',
                status: card.querySelector('.badge-status')?.textContent.trim() || 'Earned',
                description: card.querySelector('.badge-front .desc')?.textContent.trim() || '',
                icon: images[0],
                qr: qr,
                details: details,
                dateReceived: dateReceived
            };
        }

        async function saveBadgeCanvas(canvas, name, suffix) {
            var blob = await new Promise(function (resolve) { canvas.toBlob(resolve, 'image/png'); });
            if (!blob) throw new Error('The badge image could not be created.');
            var objectUrl = URL.createObjectURL(blob);
            var link = document.createElement('a');
            var safeName = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'badge';
            link.href = objectUrl;
            link.download = safeName + '-' + suffix + '.png';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1000);
        }

        async function downloadBadgeImage(card) {
            var badge = await loadBadgeDownloadData(card);
            var name = badge.name;
            var status = badge.status;
            var description = badge.description;
            var details = badge.details;
            var icon = badge.icon;
            var qr = badge.qr;

            var canvas = document.createElement('canvas');
            var width = 1200;
            var side = 76;
            var contentWidth = width - side * 2;
            var measure = canvas.getContext('2d');
            var titleFont = '700 46px Arial, sans-serif';
            var descriptionFont = '400 25px Arial, sans-serif';
            var valueFont = '400 25px Arial, sans-serif';
            var titleLines = wrapBadgeText(measure, name, contentWidth, titleFont);
            var descriptionLines = description ? wrapBadgeText(measure, description, contentWidth, descriptionFont) : [];
            var preparedRows = details.map(function (row) {
                var lines = wrapBadgeText(measure, row.value, contentWidth - 300, valueFont);
                var items = row.items.map(function (item) {
                    return wrapBadgeText(measure, item, contentWidth - 324, valueFont);
                });
                var itemLineCount = items.reduce(function (count, itemLines) { return count + itemLines.length; }, 0);
                var itemGaps = Math.max(0, items.length - 1) * 12;
                var rowContentHeight = items.length
                    ? itemLineCount * 34 + itemGaps
                    : lines.length * 34;

                return {
                    label: row.label,
                    lines: lines,
                    items: items,
                    height: Math.max(68, rowContentHeight + 28)
                };
            });
            var top = 70 + 190 + 35 + titleLines.length * 56 + (descriptionLines.length ? 24 + descriptionLines.length * 34 : 0) + 84 + 34;
            preparedRows.forEach(function (row) { top += row.height; });
            var height = top + 300;
            canvas.width = width;
            canvas.height = height;
            var context = canvas.getContext('2d');

            context.fillStyle = '#eef2f8';
            context.fillRect(0, 0, width, height);
            roundRect(context, 32, 32, width - 64, height - 64, 30);
            context.fillStyle = '#ffffff';
            context.fill();
            context.fillStyle = '#123b7a';
            roundRect(context, 32, 32, width - 64, 18, [30, 30, 0, 0]);
            context.fill();
            context.fillStyle = '#f4b900';
            context.fillRect(32, 48, width - 64, 6);

            var y = 88;
            if (icon) {
                var iconSize = 170;
                var ratio = Math.min(iconSize / icon.naturalWidth, iconSize / icon.naturalHeight);
                var iconWidth = icon.naturalWidth * ratio;
                var iconHeight = icon.naturalHeight * ratio;
                context.drawImage(icon, (width - iconWidth) / 2, y, iconWidth, iconHeight);
            } else {
                context.beginPath();
                context.arc(width / 2, y + 82, 72, 0, Math.PI * 2);
                context.fillStyle = '#e8eef8';
                context.fill();
                context.fillStyle = '#123b7a';
                context.font = '700 76px Arial, sans-serif';
                context.textAlign = 'center';
                context.fillText(name.charAt(0).toUpperCase(), width / 2, y + 108);
            }
            y += 205;

            titleLines.forEach(function (line, index) {
                context.fillStyle = '#123b7a';
                context.font = titleFont;
                context.textAlign = 'center';
                context.fillText(line, width / 2, y + index * 56);
            });
            y += titleLines.length * 56;
            if (descriptionLines.length) {
                y += 16;
                drawBadgeText(context, descriptionLines, width / 2, y, 34, '#526078', descriptionFont, 'center');
                y += descriptionLines.length * 34;
            }

            y += 28;
            context.font = '700 20px Arial, sans-serif';
            var pillWidth = Math.max(132, context.measureText(status).width + 44);
            roundRect(context, (width - pillWidth) / 2, y, pillWidth, 42, 21);
            context.fillStyle = status.toLowerCase() === 'revoked' ? '#fee4e2' : '#eaf7ee';
            context.fill();
            context.fillStyle = status.toLowerCase() === 'revoked' ? '#b42318' : '#167a3f';
            context.textAlign = 'center';
            context.fillText(status, width / 2, y + 28);
            y += 66;

            context.strokeStyle = '#d8e0eb';
            context.lineWidth = 2;
            context.beginPath();
            context.moveTo(side, y);
            context.lineTo(width - side, y);
            context.stroke();
            y += 12;

            preparedRows.forEach(function (row) {
                context.font = '700 18px Arial, sans-serif';
                context.fillStyle = '#526078';
                context.textAlign = 'left';
                context.fillText(row.label.toUpperCase(), side, y + 30);
                if (row.items.length) {
                    var itemY = y + 30;
                    row.items.forEach(function (itemLines) {
                        context.beginPath();
                        context.arc(side + 306, itemY - 8, 4, 0, Math.PI * 2);
                        context.fillStyle = '#182b4d';
                        context.fill();
                        drawBadgeText(context, itemLines, side + 324, itemY, 34, '#182b4d', valueFont, 'left');
                        itemY += itemLines.length * 34 + 12;
                    });
                } else {
                    drawBadgeText(context, row.lines, side + 300, y + 30, 34, '#182b4d', valueFont, 'left');
                }
                y += row.height;
                context.strokeStyle = '#e5eaf1';
                context.lineWidth = 1;
                context.beginPath();
                context.moveTo(side, y);
                context.lineTo(width - side, y);
                context.stroke();
            });

            y += 30;
            roundRect(context, side, y, contentWidth, 220, 20);
            context.fillStyle = '#f8fafc';
            context.fill();
            context.drawImage(qr, side + 26, y + 20, 180, 180);
            drawBadgeText(context, ['Scan to verify this badge'], side + 238, y + 76, 36, '#182b4d', '700 25px Arial, sans-serif', 'left');

            await saveBadgeCanvas(canvas, name, 'badge');
        }

        async function downloadCompactBadgeImage(card) {
            var badge = await loadBadgeDownloadData(card);
            var canvas = document.createElement('canvas');
            var width = 1040;
            var titleFont = '700 42px Arial, sans-serif';
            canvas.width = width;
            var measure = canvas.getContext('2d');
            var titleLines = wrapBadgeText(measure, badge.name, 390, titleFont);
            var titleHeight = titleLines.length * 50;
            var height = Math.max(360, titleHeight + 230);
            canvas.height = height;

            var context = canvas.getContext('2d');
            roundRect(context, 1, 1, width - 2, height - 2, 28);
            context.fillStyle = '#ffffff';
            context.fill();
            context.strokeStyle = '#d8e0eb';
            context.lineWidth = 2;
            context.stroke();
            context.fillStyle = '#123b7a';
            roundRect(context, 1, 1, width - 2, 18, [28, 28, 0, 0]);
            context.fill();
            context.fillStyle = '#f4b900';
            context.fillRect(1, 17, width - 2, 6);

            var iconSize = 240;
            var iconX = 64;
            var iconY = (height - iconSize) / 2;
            if (badge.icon) {
                var iconRatio = Math.min(iconSize / badge.icon.naturalWidth, iconSize / badge.icon.naturalHeight);
                var iconWidth = badge.icon.naturalWidth * iconRatio;
                var iconHeight = badge.icon.naturalHeight * iconRatio;
                context.drawImage(
                    badge.icon,
                    iconX + (iconSize - iconWidth) / 2,
                    iconY + (iconSize - iconHeight) / 2,
                    iconWidth,
                    iconHeight
                );
            } else {
                context.beginPath();
                context.arc(iconX + iconSize / 2, iconY + iconSize / 2, 96, 0, Math.PI * 2);
                context.fillStyle = '#e8eef8';
                context.fill();
                context.fillStyle = '#123b7a';
                context.font = '700 88px Arial, sans-serif';
                context.textAlign = 'center';
                context.fillText(badge.name.charAt(0).toUpperCase(), iconX + iconSize / 2, iconY + 125);
            }

            context.strokeStyle = '#e3e9f2';
            context.beginPath();
            context.moveTo(326, 82);
            context.lineTo(326, height - 82);
            context.stroke();

            context.font = '700 20px Arial, sans-serif';
            var pillWidth = Math.max(132, context.measureText(badge.status).width + 44);
            var statusY = Math.round((height - titleHeight - 120) / 2);
            roundRect(context, 350, statusY, pillWidth, 42, 21);
            context.fillStyle = badge.status.toLowerCase() === 'revoked' ? '#fee4e2' : '#eaf7ee';
            context.fill();
            context.fillStyle = badge.status.toLowerCase() === 'revoked' ? '#b42318' : '#167a3f';
            context.textAlign = 'center';
            context.fillText(badge.status, 350 + pillWidth / 2, statusY + 28);

            var titleY = statusY + 92;
            drawBadgeText(context, titleLines, 350, titleY, 50, '#123b7a', titleFont, 'left');
            if (badge.dateReceived) {
                context.fillStyle = '#526078';
                context.font = '400 24px Arial, sans-serif';
                context.textAlign = 'left';
                context.fillText('Earned on ' + badge.dateReceived, 350, titleY + titleHeight + 36);
            }

            var qrX = 778;
            var qrY = Math.round((height - 240) / 2);
            context.drawImage(badge.qr, qrX, qrY, 210, 210);
            context.fillStyle = '#526078';
            context.font = '700 16px Arial, sans-serif';
            context.textAlign = 'center';
            context.fillText('SCAN TO VERIFY', qrX + 105, qrY + 240);

            await saveBadgeCanvas(canvas, badge.name, 'badge-qr');
        }

        document.querySelectorAll('[data-badge-download], [data-badge-download-compact]').forEach(function (button) {
            button.addEventListener('click', async function (event) {
                event.preventDefault();
                event.stopPropagation();
                var originalText = button.querySelector('span')?.textContent || 'Download badge image';
                button.disabled = true;
                if (button.querySelector('span')) button.querySelector('span').textContent = 'Preparing image…';
                try {
                    var download = button.hasAttribute('data-badge-download-compact')
                        ? downloadCompactBadgeImage
                        : downloadBadgeImage;
                    await download(button.closest('.badge-card'));
                } catch (error) {
                    window.showFlashToast?.(error.message || 'Could not download this badge image.', 'error');
                } finally {
                    button.disabled = false;
                    if (button.querySelector('span')) button.querySelector('span').textContent = originalText;
                }
            });
        });
    })();
</script>
</x-layout>
