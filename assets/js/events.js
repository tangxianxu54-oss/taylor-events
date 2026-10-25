/* 活动列表：筛选、搜索、加载更多（AJAX） */
(() => {
    'use strict';

    const form = document.querySelector('[data-events-form]');
    const grid = document.querySelector('[data-events-grid]');
    if (!form || !grid) return;

    const chips = Array.from(document.querySelectorAll('[data-category]'));
    const countEl = document.querySelector('[data-results-count]');
    const emptyEl = document.querySelector('[data-events-empty]');
    const moreBtn = document.querySelector('[data-load-more]');
    const resetBtns = Array.from(document.querySelectorAll('[data-reset]'));
    const labelEl = document.querySelector('[data-filter-label]');
    const whenLabel = document.querySelector('[data-when-label]');

    const params = new URLSearchParams(window.location.search);
    const state = {
        q: params.get('q') || '',
        category: params.get('category') || 'all',
        when: params.get('when') || 'upcoming',
        sort: params.get('sort') || 'soonest',
        offset: 0,
    };

    const LIMIT = 9;
    let controller = null;
    let loading = false;

    form.q.value = state.q;
    form.when.value = state.when;
    form.sort.value = state.sort;
    syncChips();
    updateLabels();

    function syncChips() {
        chips.forEach((chip) => {
            chip.classList.toggle('is-active', chip.dataset.category === state.category);
        });
    }

    function buildUrl() {
        const query = new URLSearchParams({
            q: state.q,
            category: state.category,
            when: state.when,
            sort: state.sort,
            limit: String(LIMIT),
            offset: String(state.offset),
        });
        return `api/events.php?${query.toString()}`;
    }

    function syncUrl() {
        const query = new URLSearchParams();
        if (state.q) query.set('q', state.q);
        if (state.category !== 'all') query.set('category', state.category);
        if (state.when !== 'upcoming') query.set('when', state.when);
        if (state.sort !== 'soonest') query.set('sort', state.sort);

        const queryString = query.toString();
        window.history.replaceState(null, '', queryString ? `events.php?${queryString}` : 'events.php');
        updateResetVisibility();
    }

    function updateLabels() {
        const chip = chips.find((item) => item.dataset.category === state.category);
        if (labelEl && chip) {
            labelEl.textContent = chip.textContent.trim();
        }
        if (whenLabel) {
            whenLabel.textContent = form.when.options[form.when.selectedIndex]?.text || 'Upcoming';
        }
        updateResetVisibility();
    }

    function updateResetVisibility() {
        const isDefault = !state.q
            && state.category === 'all'
            && state.when === 'upcoming'
            && state.sort === 'soonest';
        resetBtns.forEach((button) => {
            button.hidden = isDefault;
        });
    }

    async function load({ append = false } = {}) {
        if (loading) return;
        loading = true;

        if (controller) controller.abort();
        controller = new AbortController();

        moreBtn.disabled = true;
        moreBtn.classList.add('is-loading');

        try {
            const response = await fetch(buildUrl(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Could not load events. Please try again.');

            const data = await response.json();
            if (!data.ok) throw new Error(data.error || 'Could not load events.');

            if (append) {
                grid.insertAdjacentHTML('beforeend', data.html);
            } else {
                grid.innerHTML = data.html;
            }

            state.offset += data.count;

            if (countEl) countEl.textContent = data.total;
            if (emptyEl) emptyEl.hidden = data.total > 0;
            moreBtn.hidden = !data.has_more;

            updateLabels();
            syncUrl();
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.appToast?.(error.message, 'error');
            }
        } finally {
            loading = false;
            moreBtn.disabled = false;
            moreBtn.classList.remove('is-loading');
        }
    }

    function resetAndLoad() {
        state.offset = 0;
        load();
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        state.q = form.q.value.trim();
        state.when = form.when.value;
        state.sort = form.sort.value;
        resetAndLoad();
    });

    form.when.addEventListener('change', () => {
        state.when = form.when.value;
        resetAndLoad();
    });

    form.sort.addEventListener('change', () => {
        state.sort = form.sort.value;
        resetAndLoad();
    });

    let debounceTimer = null;
    form.q.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            state.q = form.q.value.trim();
            resetAndLoad();
        }, 350);
    });

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            if (chip.dataset.category === state.category) return;
            state.category = chip.dataset.category;
            syncChips();
            resetAndLoad();
        });
    });

    moreBtn.addEventListener('click', () => {
        load({ append: true });
    });

    resetBtns.forEach((button) => {
        button.addEventListener('click', () => {
            state.q = '';
            state.category = 'all';
            state.when = 'upcoming';
            state.sort = 'soonest';
            form.q.value = '';
            form.when.value = 'upcoming';
            form.sort.value = 'soonest';
            syncChips();
            resetAndLoad();
        });
    });
})();
