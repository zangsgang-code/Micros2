const POLL_INTERVAL_MS = 5000;
const MAX_BACKOFF_MS = 60000;
const messageStream = document.querySelector('[data-inbox-message-stream]');
const threadList = document.querySelector('[data-inbox-thread-list]');
const userThreadList = document.querySelector('[data-user-thread-list]');

if (userThreadList) {
    const scrollArea = userThreadList.querySelector('[data-user-thread-scroll]');
    const pagination = userThreadList.querySelector('[data-user-thread-pagination]');
    const pageStatus = userThreadList.querySelector('[data-user-thread-page-status]');
    const previousPage = userThreadList.querySelector('[data-user-thread-page-prev]');
    const nextPage = userThreadList.querySelector('[data-user-thread-page-next]');
    const threads = Array.from(userThreadList.querySelectorAll('[data-user-thread-item]'));
    const threadsPerPage = 10;
    const requestedPage = Number(new URLSearchParams(window.location.search).get('threadPage'));
    let currentPage = Number.isSafeInteger(requestedPage) && requestedPage > 0 ? requestedPage : 1;

    function showThreadPage() {
        const pageCount = Math.max(1, Math.ceil(threads.length / threadsPerPage));
        currentPage = Math.min(currentPage, pageCount);
        const firstIndex = (currentPage - 1) * threadsPerPage;
        const lastIndex = Math.min(firstIndex + threadsPerPage, threads.length);

        threads.forEach((thread, index) => {
            thread.classList.toggle('inbox-thread-page-hidden', index < firstIndex || index >= lastIndex);
        });

        pagination.hidden = threads.length <= threadsPerPage;
        pageStatus.textContent = threads.length
            ? 'Showing ' + (firstIndex + 1) + '-' + lastIndex + ' of ' + threads.length
            : 'Showing 0 threads';
        previousPage.disabled = currentPage === 1;
        nextPage.disabled = currentPage === pageCount;
        threads.forEach((thread) => {
            const url = new URL(thread.href);
            if (currentPage > 1) {
                url.searchParams.set('threadPage', String(currentPage));
            } else {
                url.searchParams.delete('threadPage');
            }
            thread.href = url.toString();
        });
    }

    previousPage.addEventListener('click', () => {
        if (currentPage <= 1) return;
        currentPage -= 1;
        showThreadPage();
        scrollArea.scrollTop = 0;
    });

    nextPage.addEventListener('click', () => {
        if (currentPage >= Math.ceil(threads.length / threadsPerPage)) return;
        currentPage += 1;
        showThreadPage();
        scrollArea.scrollTop = 0;
    });

    showThreadPage();
}

if (messageStream || threadList) {
    const pollUrl = threadList?.dataset.pollUrl || messageStream?.dataset.pollUrl;
    const ownMessageClass = messageStream?.dataset.ownMessageClass;
    const incomingMessageClass = ownMessageClass === 'from-admin' ? 'from-student' : 'from-admin';
    const unreadBadge = document.querySelector('[data-inbox-unread-count]');
    const unreadSuffix = unreadBadge?.dataset.unreadSuffix || '';
    const renderedMessageIds = new Set();
    let latestMessageId = 0;
    let updatesSince = threadList?.dataset.updatesSince || null;

    if (messageStream) {
        messageStream.querySelectorAll('[data-message-id]').forEach((message) => {
            const id = Number(message.dataset.messageId);
            if (!Number.isSafeInteger(id) || id < 1) return;
            renderedMessageIds.add(id);
            latestMessageId = Math.max(latestMessageId, id);
        });
    }

    if (messageStream) {
        window.requestAnimationFrame(() => {
            messageStream.scrollTop = messageStream.scrollHeight;
        });
    }

    let nextDelayMs = POLL_INTERVAL_MS;
    let timerId = null;
    let requestInFlight = false;
    let resumeImmediatelyAfterRequest = false;
    let disposed = false;
    let hasReportedError = false;
    let activeRequest = null;

    function appendMessage(message) {
        const id = Number(message.id);
        if (!Number.isSafeInteger(id) || id < 1 || renderedMessageIds.has(id)) return;

        const isOwnMessage = ownMessageClass === 'from-admin'
            ? Boolean(message.is_admin)
            : !Boolean(message.is_admin);
        const row = document.createElement('div');
        row.className = 'msg ' + (isOwnMessage ? ownMessageClass : incomingMessageClass);
        row.dataset.messageId = String(id);

        const content = document.createElement('div');
        const author = document.createElement('div');
        author.className = 'msg-who';
        if (isOwnMessage) author.style.textAlign = 'right';
        author.textContent = (message.author || (message.is_admin ? 'Administrator' : 'You')) +
            (message.time_ago ? ' · ' + message.time_ago : '');

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        if (typeof message.body_html === 'string') {
            bubble.innerHTML = message.body_html;
        } else {
            bubble.textContent = message.body || '';
        }
        content.append(author, bubble);

        if (message.attachment_url) {
            const attachment = document.createElement('div');
            attachment.className = 'attach msg-attach';
            if (message.attachment_is_image) {
                const image = document.createElement('img');
                image.src = message.attachment_url;
                image.alt = message.attachment_name || 'Message attachment';
                image.addEventListener('click', () => {
                    if (typeof window.openLightbox === 'function') {
                        window.openLightbox(image.src);
                    }
                });
                attachment.appendChild(image);

                if (ownMessageClass === 'from-admin' && message.attachment_name) {
                    const name = document.createElement('div');
                    name.className = 'attach-name';
                    name.textContent = message.attachment_name;
                    attachment.appendChild(name);
                }
            } else {
                const link = document.createElement('a');
                link.className = 'attach-file message-attachment-link';
                link.href = message.attachment_url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.textContent = '📎 ' + (message.attachment_name || 'Attachment');
                attachment.appendChild(link);
            }
            content.appendChild(attachment);
        }

        row.appendChild(content);
        messageStream.appendChild(row);
        renderedMessageIds.add(id);
        latestMessageId = Math.max(latestMessageId, id);
    }

    function updateThreadRows(threads) {
        if (!threadList || threads.length === 0) return;

        const updatedRows = [];
        threads.forEach((thread) => {
            if (typeof thread.html !== 'string') return;

            const template = document.createElement('template');
            template.innerHTML = thread.html.trim();
            const row = template.content.firstElementChild;
            if (!row || !row.matches('.thread[data-thread-id]')) return;

            const existingRow = Array.from(threadList.querySelectorAll('.thread[data-thread-id]'))
                .find((item) => item.dataset.threadId === row.dataset.threadId);
            if (existingRow) existingRow.replaceWith(row);
            updatedRows.push(row);
        });

        if (updatedRows.length === 0) return;
        threadList.querySelector('[data-empty-threads]')?.remove();

        for (let index = updatedRows.length - 1; index >= 0; index -= 1) {
            const row = updatedRows[index];
            const firstThread = threadList.querySelector('.thread[data-thread-id]');
            const filterEmpty = threadList.querySelector('#thread-filter-empty');
            threadList.insertBefore(row, firstThread || filterEmpty);
        }

        window.dispatchEvent(new CustomEvent('admin-inbox:threads-updated'));
    }

    function updateUnreadCount(value) {
        const count = Number(value);
        if (!unreadBadge || !Number.isSafeInteger(count) || count < 0) return;
        unreadBadge.textContent = count + unreadSuffix;
        unreadBadge.style.display = count > 0 ? '' : 'none';
    }

    function clearTimer() {
        if (timerId === null) return;
        window.clearTimeout(timerId);
        timerId = null;
    }

    function scheduleNextPoll(delayMs) {
        clearTimer();
        timerId = window.setTimeout(() => {
            timerId = null;
            pollForNewMessages();
        }, delayMs);
    }

    async function pollForNewMessages() {
        if (disposed || document.hidden || requestInFlight) return;
        requestInFlight = true;
        activeRequest = new AbortController();

        try {
            const url = new URL(pollUrl, window.location.href);
            if (messageStream) {
                url.searchParams.set('sinceId', String(latestMessageId));
                if (ownMessageClass === 'from-admin') {
                    url.searchParams.set('threadId', messageStream.dataset.threadId);
                }
            }
            if (threadList && updatesSince) {
                url.searchParams.set('sinceUpdatedAt', updatesSince);
            }
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: activeRequest.signal,
            });
            if (!response.ok) throw new Error('Inbox poll returned HTTP ' + response.status);

            const data = await response.json();
            if (messageStream && !Array.isArray(data.messages)) {
                throw new Error('Inbox poll returned an invalid message list.');
            }

            if (messageStream) data.messages.forEach(appendMessage);
            if (threadList && Array.isArray(data.threads)) updateThreadRows(data.threads);
            if (threadList && typeof data.checked_at === 'string') {
                updatesSince = data.checked_at;
            }
            updateUnreadCount(data.unread_count);
            nextDelayMs = POLL_INTERVAL_MS;
            hasReportedError = false;
        } catch (error) {
            if (error.name !== 'AbortError') {
                if (!hasReportedError) {
                    console.error('Unable to poll the inbox.', error);
                    hasReportedError = true;
                }
                nextDelayMs = Math.min(nextDelayMs * 2, MAX_BACKOFF_MS);
            }
        } finally {
            requestInFlight = false;
            activeRequest = null;
            if (disposed || document.hidden) return;

            const delayMs = resumeImmediatelyAfterRequest ? 0 : nextDelayMs;
            resumeImmediatelyAfterRequest = false;
            scheduleNextPoll(delayMs);
        }
    }

    function onVisibilityChange() {
        if (document.hidden) {
            clearTimer();
            return;
        }
        if (requestInFlight) {
            resumeImmediatelyAfterRequest = true;
            return;
        }
        scheduleNextPoll(0);
    }

    function cleanup() {
        disposed = true;
        clearTimer();
        if (activeRequest) activeRequest.abort();
        document.removeEventListener('visibilitychange', onVisibilityChange);
        window.removeEventListener('pagehide', cleanup);
    }

    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('pagehide', cleanup);
    pollForNewMessages();
}
