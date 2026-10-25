/* 个人资料：AJAX 更新资料与密码 */
(() => {
    'use strict';

    const profileForm = document.querySelector('[data-profile-form]');
    const passwordForm = document.querySelector('[data-password-form]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    async function post(url, body) {
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

    function setAvatarMarkup(element, user) {
        if (!element) return;
        if (user.avatar_url) {
            element.innerHTML = `<img src="${escapeHtml(user.avatar_url)}" alt="">`;
        } else {
            element.innerHTML = escapeHtml(user.initials);
        }
    }

    /* ------------------------------------------------------------- avatar */

    const avatarInput = document.querySelector('[data-avatar-input]');
    avatarInput?.addEventListener('change', () => {
        const file = avatarInput.files?.[0];
        if (!file) return;

        const preview = document.querySelector('[data-avatar-preview]');
        if (!preview) return;

        preview.innerHTML = '';
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = 'New profile photo';
        preview.appendChild(image);
    });

    /* ------------------------------------------------------------ profile */

    profileForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submit = profileForm.querySelector('button[type="submit"]');
        submit.disabled = true;
        submit.classList.add('is-loading');

        try {
            const data = new FormData(profileForm);
            if (!data.has('_csrf')) data.append('_csrf', csrf);

            const payload = await post('api/profile.php', data);
            window.appToast?.(payload.message, 'success');

            const nameEl = document.querySelector('[data-profile-name]');
            if (nameEl) nameEl.textContent = payload.user.name;

            const roleEl = document.querySelector('[data-profile-role]');
            if (roleEl) {
                roleEl.textContent = [payload.user.major, payload.user.year].filter(Boolean).join(' · ') || 'Taylor student';
            }

            setAvatarMarkup(document.querySelector('[data-avatar-preview]'), payload.user);
            document.querySelectorAll('.user-menu__trigger .avatar').forEach((el) => {
                setAvatarMarkup(el, payload.user);
            });
        } catch (error) {
            window.appToast?.(error.message, 'error');
        } finally {
            submit.disabled = false;
            submit.classList.remove('is-loading');
        }
    });

    /* ----------------------------------------------------------- password */

    passwordForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submit = passwordForm.querySelector('button[type="submit"]');
        submit.disabled = true;
        submit.classList.add('is-loading');

        try {
            const data = new FormData(passwordForm);
            if (!data.has('_csrf')) data.append('_csrf', csrf);

            const payload = await post('api/profile.php', data);
            window.appToast?.(payload.message, 'success');
            passwordForm.reset();
        } catch (error) {
            window.appToast?.(error.message, 'error');
        } finally {
            submit.disabled = false;
            submit.classList.remove('is-loading');
        }
    });
})();
