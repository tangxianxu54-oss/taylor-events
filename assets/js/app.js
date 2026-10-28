/* Taylor Events — 全局交互 */
(() => {
    'use strict';

    const $ = (selector, context = document) => context.querySelector(selector);
    const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));
    const CSRF = $('meta[name="csrf-token"]')?.content || '';

    const ICONS = {
        success: '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 12.5 5 5 10-11"/></svg>',
        error: '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>',
        info: '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>',
    };

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    /* ------------------------------------------------------------- toasts */

    function toast(message, type = 'info') {
        const host = $('#toast-host');
        if (!host || !message) return;

        const el = document.createElement('div');
        el.className = `toast toast--${type}`;
        el.innerHTML = `${ICONS[type] || ICONS.info}<span>${escapeHtml(message)}</span>`;
        host.appendChild(el);

        setTimeout(() => {
            el.classList.add('is-leaving');
            setTimeout(() => el.remove(), 220);
        }, 4200);
    }

    window.appToast = toast;
    (window.__FLASH__ || []).forEach((item) => toast(item.message, item.type || 'info'));

    /* ------------------------------------------------------------ nav menu */

    const nav = $('#primary-nav');
    const menuToggle = $('[data-menu-toggle]');

    menuToggle?.addEventListener('click', () => {
        const open = nav.classList.toggle('is-open');
        menuToggle.setAttribute('aria-expanded', String(open));
        menuToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });

    nav?.addEventListener('click', (event) => {
        if (event.target.closest('a') && nav.classList.contains('is-open')) {
            nav.classList.remove('is-open');
            menuToggle?.setAttribute('aria-expanded', 'false');
        }
    });

    const userMenu = $('[data-user-menu]');
    const userTrigger = $('[data-user-toggle]');
    const userPanel = $('[data-user-panel]');

    function closeUserMenu() {
        if (!userPanel) return;
        userPanel.hidden = true;
        userTrigger?.setAttribute('aria-expanded', 'false');
    }

    userTrigger?.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = userPanel.hidden;
        userPanel.hidden = !open;
        userTrigger.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', (event) => {
        if (userMenu && !userMenu.contains(event.target)) {
            closeUserMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeUserMenu();
        if (nav?.classList.contains('is-open')) {
            nav.classList.remove('is-open');
            menuToggle?.setAttribute('aria-expanded', 'false');
        }
    });

    /* --------------------------------------------------------- confirm dialog */

    const modal = $('#confirm-modal');
    let confirmResolve = null;

    function closeConfirm(result) {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        const resolve = confirmResolve;
        confirmResolve = null;
        resolve?.(result);
    }

    function confirmDialog(title, message, confirmLabel = 'Confirm', danger = false) {
        return new Promise((resolve) => {
            if (!modal) {
                resolve(window.confirm(message));
                return;
            }
            confirmResolve = resolve;
            $('[data-confirm-title]', modal).textContent = title;
            $('[data-confirm-message]', modal).textContent = message;

            const ok = $('[data-confirm-ok]', modal);
            ok.textContent = confirmLabel;
            ok.className = `btn ${danger ? 'btn--danger' : 'btn--primary'}`;

            modal.hidden = false;
            document.body.classList.add('modal-open');
            ok.focus();
        });
    }

    $$('[data-confirm-cancel]', modal || document).forEach((el) => {
        el.addEventListener('click', () => closeConfirm(false));
    });
    $('[data-confirm-ok]', modal || document)?.addEventListener('click', () => closeConfirm(true));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeConfirm(false);
        }
    });

    /* ------------------------------------------------------------ ajax utils */

    async function postAjax(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body,
        });

        let payload;
        try {
            payload = await response.json();
        } catch {
            throw new Error('Unexpected server response. Please try again.');
        }

        if (!payload.ok) {
            throw new Error(payload.error || 'Something went wrong.');
        }
        return payload;
    }

    function withCsrf() {
        const data = new FormData();
        data.append('_csrf', CSRF);
        return data;
    }

    /* ------------------------------------------------------------ booking */

    function updateBooking(data) {
        const actions = $('[data-booking-actions]');
        if (actions && data.booking_html) {
            actions.innerHTML = data.booking_html;
        }
        const going = $('[data-going-count]');
        if (going) going.textContent = data.going_count;

        const left = $('[data-spots-left]');
        if (left) left.textContent = `${data.spots_left} spots left`;

        const fill = $('[data-fill]');
        if (fill) fill.style.width = `${data.fill_percent}%`;

        const progress = $('[data-progress]');
        if (progress) progress.classList.toggle('progress--full', Boolean(data.is_full));
    }

    async function handleRegister(button) {
        const id = button.dataset.eventId;
        const action = button.dataset.register;
        if (!id) return;

        if (action === 'cancel') {
            const ok = await confirmDialog(
                'Cancel your RSVP?',
                'Your spot will be released to other students. You can sign up again while space remains.',
                'Cancel RSVP',
                true
            );
            if (!ok) return;
        }

        button.disabled = true;
        button.classList.add('is-loading');
        try {
            const data = withCsrf();
            data.append('id', id);
            data.append('action', action);
            const payload = await postAjax('api/registrations.php', data);
            toast(payload.message, 'success');
            updateBooking(payload);
        } catch (error) {
            toast(error.message, 'error');
            if (button.isConnected) {
                button.disabled = false;
                button.classList.remove('is-loading');
            }
        }
    }

    async function handleCancelRsvp(button) {
        const id = button.dataset.cancelRsvp;
        if (!id) return;

        const ok = await confirmDialog(
            'Cancel this RSVP?',
            'Your spot will be released to other students.',
            'Cancel RSVP',
            true
        );
        if (!ok) return;

        button.disabled = true;
        try {
            const data = withCsrf();
            data.append('id', id);
            data.append('action', 'cancel');
            const payload = await postAjax('api/registrations.php', data);
            toast(payload.message, 'success');

            const row = button.closest('[data-going-event]');
            if (!row) {
                updateBooking(payload);
                return;
            }

            const wasUpcoming = !row.classList.contains('is-past');
            row.classList.add('is-removing');
            setTimeout(() => {
                row.remove();
                if (wasUpcoming) {
                    const stat = $('[data-stat-going]');
                    if (stat) {
                        stat.textContent = String(Math.max(0, parseInt(stat.textContent, 10) - 1));
                    }
                }
                if (!$('[data-going-event]')) {
                    window.location.reload();
                }
            }, 300);
        } catch (error) {
            toast(error.message, 'error');
            button.disabled = false;
        }
    }

    async function handleDeleteEvent(button) {
        const id = button.dataset.deleteEvent;
        if (!id) return;

        const ok = await confirmDialog(
            'Delete this event?',
            'Every registration will be removed with it. This cannot be undone.',
            'Delete event',
            true
        );
        if (!ok) return;

        button.disabled = true;
        try {
            const data = withCsrf();
            data.append('id', id);
            data.append('action', 'delete');
            const payload = await postAjax('api/events.php', data);
            toast(payload.message || 'Event deleted.', 'success');
            setTimeout(() => {
                window.location.href = 'my-events.php?tab=hosting';
            }, 500);
        } catch (error) {
            toast(error.message, 'error');
            button.disabled = false;
        }
    }

    /* ------------------------------------------------- workshop booking */

    function updateWorkshopBooking(data) {
        const actions = $('[data-booking-actions]');
        if (actions && data.booking_html) {
            actions.innerHTML = data.booking_html;
        }
        const going = $('[data-going-count]');
        if (going) going.textContent = data.going_count;

        const left = $('[data-spots-left]');
        if (left) left.textContent = `${data.spots_left} seats left`;

        const fill = $('[data-fill]');
        if (fill) fill.style.width = `${data.fill_percent}%`;

        const progress = $('[data-progress]');
        if (progress) progress.classList.toggle('progress--full', Boolean(data.is_full));
    }

    async function handleWorkshopBooking(button) {
        const id = button.dataset.workshopId;
        const action = button.dataset.workshopBook;
        if (!id) return;

        if (action === 'cancel') {
            const ok = await confirmDialog(
                'Cancel this booking?',
                'Your seat will be released to other students. You can book again while seats remain.',
                'Cancel booking',
                true
            );
            if (!ok) return;
        }

        button.disabled = true;
        button.classList.add('is-loading');
        try {
            const data = withCsrf();
            data.append('id', id);
            data.append('action', action);
            const payload = await postAjax('api/workshop-bookings.php', data);
            toast(payload.message, 'success');
            updateWorkshopBooking(payload);
        } catch (error) {
            toast(error.message, 'error');
            if (button.isConnected) {
                button.disabled = false;
                button.classList.remove('is-loading');
            }
        }
    }

    async function handleCancelBooking(button) {
        const id = button.dataset.cancelBooking;
        if (!id) return;

        const ok = await confirmDialog(
            'Cancel this booking?',
            'Your seat will be released to other students. Your booking code stops working.',
            'Cancel booking',
            true
        );
        if (!ok) return;

        button.disabled = true;
        try {
            const data = withCsrf();
            data.append('id', id);
            data.append('action', 'cancel');
            const payload = await postAjax('api/workshop-bookings.php', data);
            toast(payload.message, 'success');

            const row = button.closest('[data-booked-workshop]');
            if (!row) return;

            row.classList.add('is-removing');
            setTimeout(() => {
                row.remove();
                const stat = $('[data-stat-booked]');
                if (stat) {
                    stat.textContent = String(Math.max(0, parseInt(stat.textContent, 10) - 1));
                }
                if (!$('[data-booked-workshop]')) {
                    window.location.reload();
                }
            }, 300);
        } catch (error) {
            toast(error.message, 'error');
            button.disabled = false;
        }
    }

    async function handleDeleteWorkshop(button) {
        const id = button.dataset.deleteWorkshop;
        if (!id) return;

        const ok = await confirmDialog(
            'Delete this workshop?',
            'Every booking and booking code will be removed. This cannot be undone.',
            'Delete workshop',
            true
        );
        if (!ok) return;

        button.disabled = true;
        try {
            const data = withCsrf();
            data.append('id', id);
            data.append('action', 'delete');
            const payload = await postAjax('api/workshops.php', data);
            toast(payload.message || 'Workshop deleted.', 'success');
            setTimeout(() => {
                window.location.href = 'my-bookings.php?tab=hosting';
            }, 500);
        } catch (error) {
            toast(error.message, 'error');
            button.disabled = false;
        }
    }

    document.addEventListener('click', async (event) => {
        const register = event.target.closest('[data-register]');
        if (register) {
            event.preventDefault();
            await handleRegister(register);
            return;
        }

        const cancel = event.target.closest('[data-cancel-rsvp]');
        if (cancel) {
            event.preventDefault();
            await handleCancelRsvp(cancel);
            return;
        }

        const remove = event.target.closest('[data-delete-event]');
        if (remove) {
            event.preventDefault();
            await handleDeleteEvent(remove);
            return;
        }

        const workshopBook = event.target.closest('[data-workshop-book]');
        if (workshopBook) {
            event.preventDefault();
            await handleWorkshopBooking(workshopBook);
            return;
        }

        const cancelBooking = event.target.closest('[data-cancel-booking]');
        if (cancelBooking) {
            event.preventDefault();
            await handleCancelBooking(cancelBooking);
            return;
        }

        const removeWorkshop = event.target.closest('[data-delete-workshop]');
        if (removeWorkshop) {
            event.preventDefault();
            await handleDeleteWorkshop(removeWorkshop);
            return;
        }

        const copy = event.target.closest('[data-copy-link]');
        if (copy) {
            event.preventDefault();
            try {
                await navigator.clipboard.writeText(window.location.href);
                toast('Link copied to your clipboard.', 'success');
            } catch {
                toast('Could not copy the link. Copy it from the address bar.', 'error');
            }
            return;
        }

        const fillDemo = event.target.closest('[data-fill-demo]');
        if (fillDemo) {
            event.preventDefault();
            const form = $('.auth-form');
            if (form && form.email && form.password) {
                form.email.value = 'emma.carter@taylor.edu';
                form.password.value = 'password123';
                form.password.focus();
            }
            return;
        }

        const fillCode = event.target.closest('[data-fill-code]');
        if (fillCode) {
            event.preventDefault();
            const input = $('input[name="code"]');
            if (input) {
                input.value = fillCode.dataset.fillCode || '';
                input.focus();
            }
        }
    });

    /* --------------------------------------------------------- cover preview */

    const coverInput = $('[data-cover-input]');
    coverInput?.addEventListener('change', () => {
        const file = coverInput.files?.[0];
        if (!file) return;
        const preview = $('[data-cover-preview]');
        if (!preview) return;

        preview.innerHTML = '';
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = 'Selected cover image';
        preview.appendChild(image);
    });

    /* ---------------------------------------------------- header scroll state */

    const header = $('#site-header');
    if (header) {
        const onScroll = () => {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }
})();
