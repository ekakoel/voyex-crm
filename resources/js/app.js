import './bootstrap';
import './service-map';

import Alpine from 'alpinejs';
import L from 'leaflet';
import 'leaflet.markercluster';


window.Alpine = Alpine;
window.L = window.L || L;

Alpine.start();

const WYSIWYG_SELECTOR = 'textarea:not([data-wysiwyg-initialized]):not([data-wysiwyg="false"])';
const HTML_TAG_PATTERN = /<[^>]+>/;
const IMAGE_PREVIEW_SELECTOR = '.image-preview';

function htmlToPlainText(value) {
    const html = String(value || '');
    if (html.trim() === '') {
        return '';
    }

    const temp = document.createElement('div');
    temp.innerHTML = html;

    temp.querySelectorAll('br').forEach((node) => node.replaceWith('\n'));
    temp.querySelectorAll('p,div,li,h1,h2,h3,h4,h5,h6,blockquote').forEach((node) => {
        if (node.nextSibling) {
            node.insertAdjacentText('afterend', '\n');
        }
    });

    return String(temp.textContent || '')
        .replace(/\u00a0/g, ' ')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

function sanitizeEditorHtml(value) {
    const html = String(value || '');
    if (html.trim() === '') {
        return '';
    }

    const temp = document.createElement('div');
    temp.innerHTML = html;

    temp.querySelectorAll('script,style').forEach((node) => node.remove());
    temp.querySelectorAll('*').forEach((node) => {
        Array.from(node.attributes).forEach((attribute) => {
            const name = String(attribute.name || '').toLowerCase();
            const attrValue = String(attribute.value || '');
            if (name.startsWith('on')) {
                node.removeAttribute(attribute.name);
                return;
            }
            if (name === 'href' && /^(javascript:|data:|vbscript:)/i.test(attrValue.trim())) {
                node.setAttribute('href', '#');
            }
        });
    });

    return temp.innerHTML;
}

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function textareaValueToHtml(value) {
    const text = String(value || '');
    if (text.trim() === '') {
        return '';
    }

    if (HTML_TAG_PATTERN.test(text)) {
        return sanitizeEditorHtml(text);
    }

    // Render plain-text line breaks as HTML while keeping existing tags safe by default.
    return escapeHtml(text).replace(/\n/g, '<br>');
}

function normalizeHtmlForTextarea(html) {
    const value = String(html || '').trim();
    if (value === '' || value === '<br>' || value === '<div><br></div>' || value === '<p><br></p>') {
        return '';
    }
    return value;
}

function setupToolbarButton(button, editor) {
    const command = String(button.dataset.command || '');
    const value = button.dataset.value ?? null;

    button.addEventListener('click', (event) => {
        event.preventDefault();
        editor.focus();

        if (command === 'createLink') {
            const url = window.prompt('Masukkan URL (contoh: https://example.com)');
            if (!url) {
                return;
            }
            document.execCommand('createLink', false, url);
            return;
        }

        if (command === 'removeFormat') {
            document.execCommand('removeFormat');
            document.execCommand('unlink');
            return;
        }

        document.execCommand(command, false, value);
    });
}

function buildWysiwyg(textarea) {
    const wrapper = document.createElement('div');
    wrapper.className = 'wysiwyg mt-1';

    const toolbar = document.createElement('div');
    toolbar.className = 'wysiwyg-toolbar';
    toolbar.innerHTML = `
        <button type="button" data-command="bold" title="Bold"><strong>B</strong></button>
        <button type="button" data-command="italic" title="Italic"><em>I</em></button>
        <button type="button" data-command="underline" title="Underline"><u>U</u></button>
        <span class="wysiwyg-separator"></span>
        <button type="button" data-command="formatBlock" data-value="H2" title="Heading">H2</button>
        <button type="button" data-command="formatBlock" data-value="H3" title="Subheading">H3</button>
        <button type="button" data-command="formatBlock" data-value="P" title="Paragraph">P</button>
        <span class="wysiwyg-separator"></span>
        <button type="button" data-command="insertUnorderedList" title="Bullet List">• List</button>
        <button type="button" data-command="insertOrderedList" title="Number List">1. List</button>
        <button type="button" data-command="formatBlock" data-value="BLOCKQUOTE" title="Quote">"</button>
        <span class="wysiwyg-separator"></span>
        <button type="button" data-command="createLink" title="Link">Link</button>
        <button type="button" data-command="removeFormat" title="Clear Format">Clear</button>
    `;

    const editor = document.createElement('div');
    editor.className = 'wysiwyg-editor';
    editor.contentEditable = 'true';
    editor.dataset.placeholder = textarea.getAttribute('placeholder') || 'Write here...';
    editor.innerHTML = textareaValueToHtml(textarea.value);

    const syncToTextarea = () => {
        textarea.value = normalizeHtmlForTextarea(editor.innerHTML);
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    };

    editor.addEventListener('input', syncToTextarea);
    editor.addEventListener('blur', syncToTextarea);

    toolbar.querySelectorAll('button[data-command]').forEach((button) => {
        setupToolbarButton(button, editor);
    });

    wrapper.appendChild(toolbar);
    wrapper.appendChild(editor);
    textarea.insertAdjacentElement('afterend', wrapper);

    textarea.style.position = 'absolute';
    textarea.style.left = '-9999px';
    textarea.style.top = '0';
    textarea.style.width = '1px';
    textarea.style.height = '1px';
    textarea.style.opacity = '0';
    textarea.style.pointerEvents = 'none';
    textarea.setAttribute('data-wysiwyg-initialized', '1');
    textarea.setAttribute('data-wysiwyg-hidden', '1');

    const form = textarea.closest('form');
    if (form && form.dataset.wysiwygSubmitBound !== '1') {
        form.addEventListener('submit', () => {
            form.querySelectorAll('textarea[data-wysiwyg-initialized="1"]').forEach((field) => {
                const currentEditor = field.nextElementSibling?.querySelector('.wysiwyg-editor');
                if (!currentEditor) {
                    return;
                }
                field.value = normalizeHtmlForTextarea(currentEditor.innerHTML);
            });
        });
        form.dataset.wysiwygSubmitBound = '1';
    }
}

function initTailwindWysiwyg(root = document) {
    root.querySelectorAll(WYSIWYG_SELECTOR).forEach((textarea) => buildWysiwyg(textarea));
}

function buildImagePreviewPlaceholder(container) {
    if (container.querySelector('.image-preview-placeholder')) {
        return;
    }

    const legacyPlaceholder = container.querySelector('[data-cover-placeholder]');
    if (legacyPlaceholder) {
        legacyPlaceholder.remove();
    }

    const placeholder = document.createElement('div');
    placeholder.className = 'image-preview-placeholder';
    placeholder.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
            <circle cx="12" cy="13" r="4"></circle>
        </svg>
        <span>Select image to preview</span>
    `;
    container.appendChild(placeholder);
}

function isImageLoaded(img) {
    if (!(img instanceof HTMLImageElement)) {
        return false;
    }
    if (!img.complete) {
        return false;
    }
    return img.naturalWidth > 0;
}

function updateImagePreviewState(container) {
    const images = Array.from(container.querySelectorAll('img'));
    const hasLoadedImage = images.some((img) => isImageLoaded(img));
    container.classList.toggle('has-image', hasLoadedImage);
    images.forEach((img) => {
        img.classList.toggle('image-loaded', isImageLoaded(img));
    });
}

function initImagePreviews(root = document) {
    root.querySelectorAll(IMAGE_PREVIEW_SELECTOR).forEach((container) => {
        buildImagePreviewPlaceholder(container);
        updateImagePreviewState(container);

        container.querySelectorAll('img').forEach((img) => {
            if (img.dataset.imagePreviewBound === '1') {
                return;
            }
            img.addEventListener('load', () => updateImagePreviewState(container));
            img.addEventListener('error', () => updateImagePreviewState(container));
            img.dataset.imagePreviewBound = '1';
        });

        if (container.dataset.imagePreviewBound === '1') {
            return;
        }

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof HTMLImageElement)) {
                        return;
                    }
                    if (node.dataset.imagePreviewBound === '1') {
                        return;
                    }
                    node.addEventListener('load', () => updateImagePreviewState(container));
                    node.addEventListener('error', () => updateImagePreviewState(container));
                    node.dataset.imagePreviewBound = '1';
                });
            });
            updateImagePreviewState(container);
        });
        observer.observe(container, { childList: true, subtree: true });
        container.dataset.imagePreviewBound = '1';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initTailwindWysiwyg(document);
    initImagePreviews(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }

                if (node.matches && node.matches(WYSIWYG_SELECTOR)) {
                    buildWysiwyg(node);
                    return;
                }

                initTailwindWysiwyg(node);
                initImagePreviews(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    if (form.dataset.disableSubmitLock === '1') {
        return;
    }
    if (form.dataset.submitLocked === '1') {
        event.preventDefault();
        return;
    }

    form.dataset.submitLocked = '1';

    const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    submitButtons.forEach((button) => {
        if (button.dataset.skipSpinner === '1') {
            return;
        }
        if (button instanceof HTMLButtonElement) {
            if (!button.querySelector('.btn-spinner')) {
                const spinner = document.createElement('span');
                spinner.className = 'btn-spinner';
                spinner.setAttribute('aria-hidden', 'true');
                button.appendChild(spinner);
            }
            button.classList.add('btn-loading');
            button.disabled = true;
        } else if (button instanceof HTMLInputElement) {
            if (!button.dataset.originalValue) {
                button.dataset.originalValue = button.value;
            }
            button.value = 'Loading...';
            button.disabled = true;
            button.classList.add('btn-loading');
        }
    });
});

const PAGE_SPINNER_SELECTOR = '[data-page-spinner]';
const PAGE_SPINNER_MODE_ATTR = 'data-page-spinner';
const PAGE_TRANSITION_PENDING_KEY = 'app_nav_progressive_pending';
const PAGE_TRANSITION_PENDING_TS_KEY = `${PAGE_TRANSITION_PENDING_KEY}_ts`;
const PAGE_TRANSITION_TTL_MS = 20000;

function setPageSpinnerVisible(visible) {
    const spinner = document.querySelector(PAGE_SPINNER_SELECTOR);
    if (!spinner) {
        return;
    }
    document.body.classList.toggle('page-loading', visible);
    spinner.setAttribute('aria-hidden', visible ? 'false' : 'true');
}

function shouldShowPageSpinnerOnForm(form) {
    if (!(form instanceof HTMLFormElement)) {
        return false;
    }
    const explicitModeHost = form.closest(`[${PAGE_SPINNER_MODE_ATTR}]`);
    const explicitMode = explicitModeHost?.getAttribute(PAGE_SPINNER_MODE_ATTR)?.toLowerCase() ?? '';

    if (explicitMode === 'on') {
        return true;
    }

    if (explicitMode === 'off') {
        return false;
    }

    if (form.dataset.skipSpinner === '1') {
        return false;
    }

    // Standard global: overlay spinner only for form submit process.
    return true;
}

function markPageTransitionPending() {
    try {
        sessionStorage.setItem(PAGE_TRANSITION_PENDING_KEY, '1');
        sessionStorage.setItem(PAGE_TRANSITION_PENDING_TS_KEY, String(Date.now()));
    } catch (_) {
        // Ignore storage restriction in private mode.
    }
}

function clearPageTransitionPending() {
    try {
        sessionStorage.removeItem(PAGE_TRANSITION_PENDING_KEY);
        sessionStorage.removeItem(PAGE_TRANSITION_PENDING_TS_KEY);
    } catch (_) {
        // Ignore storage restriction in private mode.
    }
}

function getInternalNavigationUrl(link, event) {
    if (!(link instanceof HTMLAnchorElement)) {
        return null;
    }
    if (event.defaultPrevented || event.button !== 0) {
        return null;
    }
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return null;
    }
    if (link.target && link.target !== '_self') {
        return null;
    }
    if (link.hasAttribute('download')) {
        return null;
    }
    if (link.dataset.instantNav === 'off' || link.closest('[data-instant-nav="off"]')) {
        return null;
    }
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
        return null;
    }

    let url;
    try {
        url = new URL(href, window.location.href);
    } catch (_) {
        return null;
    }

    if (url.origin !== window.location.origin) {
        return null;
    }

    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash !== '') {
        return null;
    }

    return url;
}

function renderInstantNavigationShell(link) {
    const contentRoot = document.querySelector('[data-page-progressive-content]');
    if (!contentRoot) {
        return;
    }

    const root = document.documentElement;
    const title = String(link?.textContent || '').trim() || 'Opening Page';
    const safeTitle = escapeHtml(title);
    contentRoot.innerHTML = `
        <section class="instant-nav-shell app-card p-5 sm:p-6">
            <div class="instant-nav-shell__header">
                <h2 class="instant-nav-shell__title">${safeTitle}</h2>
                <p class="instant-nav-shell__subtitle">Loading data in background...</p>
            </div>
            <div class="instant-nav-shell__list">
                <div class="instant-nav-shell__row">
                    <span class="instant-nav-shell__label">Name:</span>
                    <span class="instant-nav-shell__value is-blur">Loading...</span>
                </div>
                <div class="instant-nav-shell__row">
                    <span class="instant-nav-shell__label">Status:</span>
                    <span class="instant-nav-shell__value is-blur">Loading...</span>
                </div>
                <div class="instant-nav-shell__row">
                    <span class="instant-nav-shell__label">Summary:</span>
                    <span class="instant-nav-shell__value is-blur">Loading...</span>
                </div>
            </div>
        </section>
    `;

    root.classList.add('page-transition-pending');
    root.classList.remove('page-transition-ready');
    root.setAttribute('data-page-transition', 'pending');
    document.title = `${title} · Loading...`;
}

function bindPageProgressiveTransition() {
    const root = document.documentElement;
    let settled = false;
    let pendingTs = 0;
    try {
        pendingTs = Number(sessionStorage.getItem(PAGE_TRANSITION_PENDING_TS_KEY) || 0);
    } catch (_) {
        pendingTs = 0;
    }

    const settleTransition = () => {
        if (settled) {
            return;
        }
        settled = true;
        if (root.classList.contains('page-transition-pending')) {
            root.classList.add('page-transition-ready');
            window.setTimeout(() => {
                root.classList.remove('page-transition-pending', 'page-transition-ready');
                root.removeAttribute('data-page-transition');
            }, 240);
        }
        clearPageTransitionPending();
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        const url = getInternalNavigationUrl(link, event);
        if (!url) {
            return;
        }

        event.preventDefault();
        markPageTransitionPending();
        renderInstantNavigationShell(link);
        window.requestAnimationFrame(() => {
            window.location.assign(url.toString());
        });
    }, true);

    const stale = !Number.isFinite(pendingTs) || pendingTs <= 0 || (Date.now() - pendingTs) > PAGE_TRANSITION_TTL_MS;
    if (stale) {
        clearPageTransitionPending();
        root.classList.remove('page-transition-pending', 'page-transition-ready');
        root.removeAttribute('data-page-transition');
        return;
    }

    if (root.classList.contains('page-transition-pending')) {
        if (document.readyState === 'complete') {
            window.setTimeout(settleTransition, 80);
        } else {
            window.addEventListener('load', () => window.setTimeout(settleTransition, 80), { once: true });
            window.setTimeout(settleTransition, 1400);
        }
    } else {
        clearPageTransitionPending();
    }
}

function bindPageSpinner() {
    const spinner = document.querySelector(PAGE_SPINNER_SELECTOR);
    if (!spinner) {
        return;
    }

    setPageSpinnerVisible(false);

    let spinnerRequestedByForm = false;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!shouldShowPageSpinnerOnForm(form)) {
            return;
        }
        spinnerRequestedByForm = true;
        setPageSpinnerVisible(true);
    });

    window.addEventListener('beforeunload', () => {
        if (spinnerRequestedByForm) {
            setPageSpinnerVisible(true);
        }
    });

    window.addEventListener('pageshow', () => {
        spinnerRequestedByForm = false;
        setPageSpinnerVisible(false);
    });
}

function bindProgressiveDataReveal() {
    const pages = document.querySelectorAll('[data-progressive-dashboard]');
    if (!pages.length) {
        return;
    }

    const STEP_MS = 80;
    const GROUP_DELAY_MS = 180;

    pages.forEach((page) => {
        const groups = Array.from(page.querySelectorAll('[data-progressive-group]'));
        if (!groups.length) {
            groups.push(page);
        }

        groups.forEach((group, groupIndex) => {
            const items = Array.from(group.querySelectorAll('[data-progressive-item]'));
            if (!items.length) {
                return;
            }

            items.forEach((item) => item.classList.add('dashboard-item-pending'));
            items.forEach((item, itemIndex) => {
                const delay = (groupIndex * GROUP_DELAY_MS) + (itemIndex * STEP_MS);
                window.setTimeout(() => {
                    item.classList.remove('dashboard-item-pending');
                    item.classList.add('dashboard-item-ready');
                }, delay);
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindPageProgressiveTransition();
    bindPageSpinner();
    bindProgressiveDataReveal();
});

function ensureActionSpinner(target) {
    if (!target) {
        return null;
    }
    let spinner = target.querySelector('.btn-spinner');
    if (!spinner) {
        spinner = document.createElement('span');
        spinner.className = 'btn-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        target.appendChild(spinner);
    }
    return spinner;
}

function setActionLoading(target, isLoading, customText) {
    if (!target) {
        return;
    }
    const isButton = target instanceof HTMLButtonElement || target instanceof HTMLInputElement;
    if (isLoading) {
        if (target.dataset.actionLoading === '1') {
            return;
        }
        target.dataset.actionLoading = '1';
        if (customText && !target.dataset.actionLoadingLabel) {
            target.dataset.actionLoadingLabel = target.textContent || '';
            target.textContent = customText;
        }
        ensureActionSpinner(target);
        target.classList.add('btn-loading');
        target.setAttribute('aria-busy', 'true');
        if (isButton) {
            target.disabled = true;
        }
        return;
    }

    target.dataset.actionLoading = '0';
    target.classList.remove('btn-loading');
    target.setAttribute('aria-busy', 'false');
    if (isButton) {
        target.disabled = false;
    }
    if (target.dataset.actionLoadingLabel) {
        target.textContent = target.dataset.actionLoadingLabel;
        delete target.dataset.actionLoadingLabel;
    }
}

function bindActionLoadingButtons() {
    document.addEventListener('click', (event) => {
        const target = event.target.closest('[data-action-loading]');
        if (!target) {
            return;
        }
        if (target.dataset.actionLoading === '1') {
            event.preventDefault();
            return;
        }
        const label = target.getAttribute('data-action-loading-text') || '';
        setActionLoading(target, true, label || undefined);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindActionLoadingButtons();
});

window.AppLoading = {
    showPageSpinner: () => setPageSpinnerVisible(true),
    hidePageSpinner: () => setPageSpinnerVisible(false),
    withPageSpinner: async (promise) => {
        setPageSpinnerVisible(true);
        try {
            return await promise;
        } finally {
            setPageSpinnerVisible(false);
        }
    },
    setActionLoading: (element, isLoading, text) => setActionLoading(element, isLoading, text),
};


const HOTELS_EDITOR_SELECTOR = '[data-hotels-editor]';

function buildHotelsFlashMarkup(messages, type = 'success') {
    const items = Array.isArray(messages) ? messages.filter(Boolean) : [messages].filter(Boolean);
    if (items.length === 0) {
        return '';
    }

    const tone = type === 'error'
        ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300'
        : type === 'warning'
            ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300'
            : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300';

    return `
        <div class="rounded-lg border px-4 py-3 text-sm ${tone}">
            ${items.map((message) => `<div>${escapeHtml(String(message))}</div>`).join('')}
        </div>
    `;
}

function setHotelsFlash(editor, messages, type = 'success') {
    const flashArea = editor.querySelector('[data-hotels-flash-area]');
    if (!flashArea) {
        return;
    }
    flashArea.innerHTML = buildHotelsFlashMarkup(messages, type);
}

function replaceHotelsEditor(currentEditor, html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = String(html || '').trim();
    const nextEditor = wrapper.firstElementChild;
    if (!nextEditor) {
        throw new Error('Invalid hotels editor payload.');
    }
    currentEditor.replaceWith(nextEditor);
    return nextEditor;
}

function appendSubmitter(formData, submitter) {
    if (!submitter || !submitter.name) {
        return;
    }
    formData.append(submitter.name, submitter.value || '');
}

async function fetchHotelsEditor(url, options = {}) {
    const response = await fetch(url, {
        method: options.method || 'GET',
        body: options.body,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Hotels-Ajax': '1',
            ...(options.headers || {}),
        },
        credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok) {
        const error = new Error('Hotels editor request failed.');
        error.response = response;
        error.payload = payload;
        throw error;
    }

    return payload;
}

function syncHotelsHistory(url, replace = false) {
    if (!url) {
        return;
    }
    const state = { hotelsEditor: true, url };
    if (replace) {
        window.history.replaceState(state, '', url);
        return;
    }
    window.history.pushState(state, '', url);
}

function initHotelsEditor(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const editors = scope.matches?.(HOTELS_EDITOR_SELECTOR)
        ? [scope]
        : Array.from(scope.querySelectorAll(HOTELS_EDITOR_SELECTOR));

    editors.forEach((editor) => {
        if (editor.dataset.hotelsEditorBound === '1') {
            return;
        }
        editor.dataset.hotelsEditorBound = '1';

        editor.addEventListener('click', async (event) => {
            const link = event.target.closest('[data-hotel-step-link]');
            if (!link) {
                return;
            }
            event.preventDefault();
            if (editor.dataset.hotelsLoading === '1') {
                return;
            }

            editor.dataset.hotelsLoading = '1';
            window.AppLoading?.showPageSpinner?.();

            try {
                const payload = await fetchHotelsEditor(link.href);
                const nextEditor = replaceHotelsEditor(editor, payload?.html || '');
                syncHotelsHistory(payload?.url || link.href);
                if (payload?.warning) {
                    setHotelsFlash(nextEditor, payload.warning, 'warning');
                }
                initHotelsEditor(nextEditor);
            } catch (_) {
                window.location.href = link.href;
            } finally {
                editor.dataset.hotelsLoading = '0';
                window.AppLoading?.hidePageSpinner?.();
            }
        });

        editor.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.matches('[data-hotels-ajax-form]')) {
                return;
            }
            event.preventDefault();
            if (form.dataset.hotelsSubmitting === '1') {
                return;
            }

            const submitter = event.submitter || null;
            const formData = new FormData(form);
            appendSubmitter(formData, submitter);
            form.dataset.hotelsSubmitting = '1';
            window.AppLoading?.showPageSpinner?.();
            if (submitter) {
                window.AppLoading?.setActionLoading?.(submitter, true);
            }

            try {
                const payload = await fetchHotelsEditor(form.action, {
                    method: (form.method || 'POST').toUpperCase(),
                    body: formData,
                });
                const nextEditor = replaceHotelsEditor(editor, payload?.html || '');
                syncHotelsHistory(payload?.url || `${window.location.pathname}${window.location.search}`, false);
                if (payload?.message) {
                    setHotelsFlash(nextEditor, payload.message, 'success');
                }
                initHotelsEditor(nextEditor);
            } catch (error) {
                if (error?.response?.status === 422 && error?.payload?.errors) {
                    const messages = Object.values(error.payload.errors).flat();
                    setHotelsFlash(editor, messages, 'error');
                } else {
                    form.submit();
                    return;
                }
            } finally {
                form.dataset.hotelsSubmitting = '0';
                if (submitter) {
                    window.AppLoading?.setActionLoading?.(submitter, false);
                }
                window.AppLoading?.hidePageSpinner?.();
            }
        });
    });
}

function initHotelRooms(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const containers = Array.from(scope.querySelectorAll('#room-rows'));
    if (scope instanceof Element && scope.id === 'room-rows') {
        containers.unshift(scope);
    }

    containers.forEach((container) => {
        if (container.dataset.hotelRoomsBound === '1') {
            return;
        }
        container.dataset.hotelRoomsBound = '1';

        const editor = container.closest(HOTELS_EDITOR_SELECTOR) || document;
        const addRoomButton = editor.querySelector('[data-add-row="room"]');

        const updateRoomTitle = (card) => {
            const title = card.querySelector('[data-room-title]');
            const input = card.querySelector('[data-room-name]');
            if (!title || !input) {
                return;
            }
            const value = String(input.value || '').trim();
            const badge = card.querySelector('span');
            const fallback = badge ? `Room ${String(badge.textContent || '').trim()}` : 'Room';
            title.textContent = value !== '' ? value : fallback;
        };

        container.querySelectorAll('[data-room-card]').forEach((card) => updateRoomTitle(card));

        container.addEventListener('input', (event) => {
            const input = event.target.closest('[data-room-name]');
            if (!input) {
                return;
            }
            const card = input.closest('[data-room-card]');
            if (card) {
                updateRoomTitle(card);
            }
        });

        container.addEventListener('change', (event) => {
            const input = event.target.closest('.room-cover-input');
            if (!input) {
                return;
            }
            const card = input.closest('[data-room-card]');
            const preview = card?.querySelector('.room-cover-preview');
            const file = input.files?.[0];
            if (!preview || !file || !String(file.type || '').startsWith('image/')) {
                return;
            }
            const url = URL.createObjectURL(file);
            let img = preview.querySelector('img');
            if (!img) {
                img = document.createElement('img');
                img.className = 'h-full w-full object-cover';
                img.alt = 'Room cover preview';
                preview.appendChild(img);
            }
            img.addEventListener('load', () => {
                img.classList.add('image-loaded');
                preview.classList.add('has-image');
                URL.revokeObjectURL(url);
            }, { once: true });
            img.addEventListener('error', () => {
                preview.classList.remove('has-image');
                img.remove();
            }, { once: true });
            img.src = url;
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-room]');
            if (!button) {
                return;
            }
            const card = button.closest('[data-room-card]');
            if (card) {
                card.remove();
            }
        });

        if (addRoomButton && addRoomButton.dataset.hotelRoomsButtonBound !== '1') {
            addRoomButton.dataset.hotelRoomsButtonBound = '1';
            addRoomButton.addEventListener('click', () => {
                const idx = container.querySelectorAll('[data-room-card]').length;
                const template = editor.querySelector('#room-row-template');
                if (!template) {
                    return;
                }
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML
                    .replace(/__INDEX__/g, String(idx))
                    .replace(/__NUMBER__/g, String(idx + 1))
                    .trim();
                const card = wrapper.firstElementChild;
                if (!card) {
                    return;
                }
                container.appendChild(card);
                updateRoomTitle(card);
            });
        }
    });
}

function initHotelPrices(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const containers = Array.from(scope.querySelectorAll('#price-rows'));
    if (scope instanceof Element && scope.id === 'price-rows') {
        containers.unshift(scope);
    }

    const parseMoney = (value) => {
        const raw = String(value ?? '').trim();
        if (raw === '') {
            return 0;
        }

        if (/^\d+([.,]\d{1,2})?$/.test(raw) && !raw.includes(' ')) {
            const numeric = Number(raw.replace(',', '.'));
            return Number.isFinite(numeric) ? Math.max(0, numeric) : 0;
        }

        const digits = raw.replace(/[^\d]/g, '');
        if (digits === '') {
            return 0;
        }

        const numeric = Number(digits);
        return Number.isFinite(numeric) ? Math.max(0, numeric) : 0;
    };

    const recalcRowPublishRate = (row) => {
        if (!(row instanceof Element)) {
            return;
        }

        const contractInput = row.querySelector('[data-hotel-rate="contract"]');
        const markupTypeSelect = row.querySelector('[data-hotel-rate="markup_type"]');
        const markupInput = row.querySelector('[data-hotel-rate="markup"]');
        const publishInput = row.querySelector('[data-hotel-rate="publish"]');
        if (!contractInput || !markupTypeSelect || !markupInput || !publishInput) {
            return;
        }

        const contractRate = parseMoney(contractInput.value);
        let markupValue = parseMoney(markupInput.value);
        const markupType = markupTypeSelect.value === 'percent' ? 'percent' : 'fixed';

        if (markupType === 'percent' && markupValue > 100) {
            markupValue = 100;
            markupInput.value = '100';
        }

        const publishRate = markupType === 'percent'
            ? contractRate + (contractRate * (markupValue / 100))
            : contractRate + markupValue;

        publishInput.value = String(Math.max(0, Math.round(publishRate)));
    };

    containers.forEach((container) => {
        if (container.dataset.hotelPricesBound === '1') {
            return;
        }
        container.dataset.hotelPricesBound = '1';

        const editor = container.closest(HOTELS_EDITOR_SELECTOR) || document;
        const addPriceButton = editor.querySelector('[data-add-row="price"]');

        if (addPriceButton && addPriceButton.dataset.hotelPricesButtonBound !== '1') {
            addPriceButton.dataset.hotelPricesButtonBound = '1';
            addPriceButton.addEventListener('click', () => {
                const idx = container.children.length;
                const sourceSelect = container.querySelector('select[name*="[rooms_id]"]');
                const roomOptions = sourceSelect
                    ? Array.from(sourceSelect.options)
                        .filter((option) => option.value !== '')
                        .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.textContent || '')}</option>`)
                        .join('')
                    : '';
                const wrapper = document.createElement('div');
                wrapper.innerHTML = `
                    <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700 md:grid-cols-12" data-row>
                        <div class="md:col-span-4">
                            <label class="block text-xs text-gray-500">Room</label>
                            <select name="hotel_prices[${idx}][rooms_id]" class="mt-1 app-input">
                                <option value="">Select room</option>
                                ${roomOptions}
                            </select>
                        </div>
                        <div class="md:col-span-4">                            <label class="block text-xs text-gray-500">Start Date</label>
                            <input type="date" name="hotel_prices[${idx}][start_date]" class="mt-1 app-input">
                        </div>
                        <div class="md:col-span-4">                            <label class="block text-xs text-gray-500">End Date</label>
                            <input type="date" name="hotel_prices[${idx}][end_date]" class="mt-1 app-input">
                        </div>
                        <div class="md:col-span-3">                            <label class="block text-xs text-gray-500">Contract Rate (IDR)</label>
                            <input type="number" min="0" step="1" name="hotel_prices[${idx}][contract_rate]" data-hotel-rate="contract" class="mt-1 app-input">
                        </div>
                        <div class="md:col-span-3">                            <label class="block text-xs text-gray-500">Markup Type</label>
                            <select name="hotel_prices[${idx}][markup_type]" data-hotel-rate="markup_type" class="mt-1 app-input">
                                <option value="fixed">Fixed</option>
                                <option value="percent">Percent</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">                            <label class="block text-xs text-gray-500">Markup</label>
                            <input type="number" min="0" step="1" name="hotel_prices[${idx}][markup]" data-hotel-rate="markup" class="mt-1 app-input">
                        </div>
                        <div class="md:col-span-3">                            <label class="block text-xs text-gray-500">Publish Rate (Auto)</label>
                            <input type="number" min="0" step="1" name="hotel_prices[${idx}][publish_rate]" data-hotel-rate="publish" class="mt-1 app-input" readonly>
                        </div>

                        <div class="md:col-span-12 flex justify-end">
                            <button type="button" class="mt-1 btn-ghost-sm h-[38px] w-full md:w-auto" data-remove-row>Remove</button>
                        </div>
                    </div>
                `;
                const row = wrapper.firstElementChild;
                if (!row) {
                    return;
                }
                container.appendChild(row);
                recalcRowPublishRate(row);
            });
        }

        container.addEventListener('input', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement)) {
                return;
            }
            if (!target.matches('[data-hotel-rate="contract"], [data-hotel-rate="markup"], [data-hotel-rate="markup_type"]')) {
                return;
            }
            const row = target.closest('[data-row]');
            recalcRowPublishRate(row);
        });

        container.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement)) {
                return;
            }
            if (!target.matches('[data-hotel-rate="contract"], [data-hotel-rate="markup"], [data-hotel-rate="markup_type"]')) {
                return;
            }
            const row = target.closest('[data-row]');
            recalcRowPublishRate(row);
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-row]');
            if (!button) {
                return;
            }
            const row = button.closest('[data-row]');
            if (row) {
                row.remove();
            }
        });

        container.querySelectorAll('[data-row]').forEach((row) => recalcRowPublishRate(row));
    });
}
function initHotelInfoCover(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const wrappers = scope.matches?.('[data-hotel-info-cover]')
        ? [scope]
        : Array.from(scope.querySelectorAll('[data-hotel-info-cover]'));

    wrappers.forEach((wrapper) => {
        if (wrapper.dataset.hotelInfoCoverBound === '1') {
            return;
        }
        wrapper.dataset.hotelInfoCoverBound = '1';

        const input = wrapper.querySelector('.hotel-cover-input');
        const preview = wrapper.closest('div')?.querySelector('.hotel-cover-preview') || wrapper.parentElement?.querySelector('.hotel-cover-preview');
        if (!input || !preview) {
            return;
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file || !String(file.type || '').startsWith('image/')) {
                return;
            }
            const url = URL.createObjectURL(file);
            let image = preview.querySelector('img');
            if (!image) {
                image = document.createElement('img');
                image.className = 'h-full w-full object-cover';
                image.alt = 'Hotel cover preview';
                preview.appendChild(image);
            }
            image.addEventListener('load', () => {
                image.classList.add('image-loaded');
                preview.classList.add('has-image');
                URL.revokeObjectURL(url);
            }, { once: true });
            image.addEventListener('error', () => {
                preview.classList.remove('has-image');
                image.remove();
            }, { once: true });
            image.src = url;
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initHotelsEditor(document);
    initHotelRooms(document);
    initHotelPrices(document);
    initHotelInfoCover(document);

    window.addEventListener('popstate', async () => {
        const editor = document.querySelector(HOTELS_EDITOR_SELECTOR);
        if (!editor) {
            return;
        }
        try {
            const payload = await fetchHotelsEditor(window.location.href);
            const nextEditor = replaceHotelsEditor(editor, payload?.html || '');
            initHotelsEditor(nextEditor);
            initHotelRooms(nextEditor);
            initHotelPrices(nextEditor);
            initHotelInfoCover(nextEditor);
        } catch (_) {
            window.location.reload();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                initHotelsEditor(node);
                initHotelRooms(node);
                initHotelPrices(node);
                initHotelInfoCover(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});


const HOTELS_INDEX_SELECTOR = '[data-hotels-index]';

function buildHotelsIndexQuery(form) {
    const formData = new FormData(form);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
        const stringValue = String(value ?? '').trim();
        if (stringValue === '') {
            continue;
        }
        params.set(key, stringValue);
    }

    return params.toString();
}

function syncHotelsFilterForm(form, url) {
    if (!form) {
        return;
    }
    const parsed = new URL(url, window.location.origin);
    const params = parsed.searchParams;

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
        const name = field.getAttribute('name');
        if (!name) {
            return;
        }
        const nextValue = params.get(name) || '';
        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
            field.value = nextValue;
        }
    });
}

async function fetchHotelsIndex(url) {
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Hotels-Ajax': '1',
        },
        credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok) {
        const error = new Error('Hotels index request failed.');
        error.response = response;
        error.payload = payload;
        throw error;
    }

    return payload;
}

function initHotelsIndex(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const containers = scope.matches?.(HOTELS_INDEX_SELECTOR)
        ? [scope]
        : Array.from(scope.querySelectorAll(HOTELS_INDEX_SELECTOR));

    containers.forEach((container) => {
        if (container.dataset.hotelsIndexBound === '1') {
            return;
        }
        container.dataset.hotelsIndexBound = '1';

        const form = container.querySelector('[data-hotels-index-form]');
        const resultsWrap = container.querySelector('[data-hotels-index-results-wrap]');
        if (!form || !resultsWrap) {
            return;
        }

        let typingTimer = null;

        const setLoading = (isLoading) => {
            resultsWrap.classList.toggle('opacity-60', isLoading);
            resultsWrap.classList.toggle('pointer-events-none', isLoading);
            if (isLoading) {
                window.AppLoading?.showPageSpinner?.();
                return;
            }
            window.AppLoading?.hidePageSpinner?.();
        };

        const requestAndRender = async (url, updateHistory = true) => {
            setLoading(true);
            try {
                const payload = await fetchHotelsIndex(url);
                if (payload?.html) {
                    resultsWrap.innerHTML = payload.html;
                }
                if (updateHistory) {
                    window.history.replaceState({ hotelsIndex: true }, '', payload?.url || url);
                }
            } catch (_) {
                window.location.href = url;
            } finally {
                setLoading(false);
            }
        };

        const submitFilters = () => {
            const query = buildHotelsIndexQuery(form);
            const action = form.getAttribute('action') || window.location.pathname;
            const url = query !== '' ? `${action}?${query}` : action;
            requestAndRender(url, true);
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilters();
        });

        form.addEventListener('input', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.matches('[data-hotels-filter-input]')) {
                return;
            }
            clearTimeout(typingTimer);
            typingTimer = window.setTimeout(() => {
                submitFilters();
            }, 350);
        });

        form.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLSelectElement) || !target.matches('[data-hotels-filter-input]')) {
                return;
            }
            submitFilters();
        });

        const resetLink = form.querySelector('[data-hotels-filter-reset]');
        if (resetLink) {
            resetLink.addEventListener('click', (event) => {
                event.preventDefault();
                form.reset();
                submitFilters();
            });
        }

        resultsWrap.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) {
                return;
            }
            const href = link.getAttribute('href');
            if (!href) {
                return;
            }

            let parsed;
            try {
                parsed = new URL(href, window.location.origin);
            } catch (_) {
                return;
            }

            if (parsed.origin !== window.location.origin) {
                return;
            }

            if (!parsed.searchParams.has('page')) {
                return;
            }

            event.preventDefault();
            syncHotelsFilterForm(form, parsed.toString());
            requestAndRender(parsed.toString(), true);
        });

        if (!window.__hotelsIndexPopstateBound) {
            window.__hotelsIndexPopstateBound = true;
            window.addEventListener('popstate', () => {
                const activeContainer = document.querySelector(HOTELS_INDEX_SELECTOR);
                if (!activeContainer) {
                    return;
                }
                const activeForm = activeContainer.querySelector('[data-hotels-index-form]');
                const activeResults = activeContainer.querySelector('[data-hotels-index-results-wrap]');
                if (!activeForm || !activeResults) {
                    return;
                }
                syncHotelsFilterForm(activeForm, window.location.href);
                requestAndRender(window.location.href, false);
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initHotelsIndex(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                initHotelsIndex(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

const ACTIVITIES_INDEX_SELECTOR = '[data-activities-index]';

function buildActivitiesIndexQuery(form) {
    const formData = new FormData(form);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
        const stringValue = String(value ?? '').trim();
        if (stringValue === '') {
            continue;
        }
        params.set(key, stringValue);
    }

    return params.toString();
}

function syncActivitiesFilterForm(form, url) {
    if (!form) {
        return;
    }
    const parsed = new URL(url, window.location.origin);
    const params = parsed.searchParams;

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
        const name = field.getAttribute('name');
        if (!name) {
            return;
        }
        const nextValue = params.get(name) || '';
        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
            field.value = nextValue;
        }
    });
}

async function fetchActivitiesIndex(url) {
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Activities-Ajax': '1',
        },
        credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok) {
        const error = new Error('Activities index request failed.');
        error.response = response;
        error.payload = payload;
        throw error;
    }

    return payload;
}

function initActivitiesIndex(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const containers = scope.matches?.(ACTIVITIES_INDEX_SELECTOR)
        ? [scope]
        : Array.from(scope.querySelectorAll(ACTIVITIES_INDEX_SELECTOR));

    containers.forEach((container) => {
        if (container.dataset.activitiesIndexBound === '1') {
            return;
        }
        container.dataset.activitiesIndexBound = '1';

        const form = container.querySelector('[data-activities-index-form]');
        const resultsWrap = container.querySelector('[data-activities-index-results-wrap]');
        if (!form || !resultsWrap) {
            return;
        }

        const setLoading = (isLoading) => {
            resultsWrap.classList.toggle('opacity-60', isLoading);
            resultsWrap.classList.toggle('pointer-events-none', isLoading);
            if (isLoading) {
                window.AppLoading?.showPageSpinner?.();
                return;
            }
            window.AppLoading?.hidePageSpinner?.();
        };

        const requestAndRender = async (url, updateHistory = true) => {
            setLoading(true);
            try {
                const payload = await fetchActivitiesIndex(url);
                if (payload?.html) {
                    resultsWrap.innerHTML = payload.html;
                }
                if (updateHistory) {
                    window.history.replaceState({ activitiesIndex: true }, '', payload?.url || url);
                }
            } catch (_) {
                window.location.href = url;
            } finally {
                setLoading(false);
            }
        };

        const submitFilters = () => {
            const query = buildActivitiesIndexQuery(form);
            const action = form.getAttribute('action') || window.location.pathname;
            const url = query !== '' ? `${action}?${query}` : action;
            requestAndRender(url, true);
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilters();
        });

        form.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) {
                return;
            }
            if (!target.matches('[data-activities-filter-input]')) {
                return;
            }
            submitFilters();
        });

        const resetLink = form.querySelector('[data-activities-filter-reset]');
        if (resetLink) {
            resetLink.addEventListener('click', (event) => {
                event.preventDefault();
                form.reset();
                submitFilters();
            });
        }

        resultsWrap.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) {
                return;
            }
            const href = link.getAttribute('href');
            if (!href) {
                return;
            }

            let parsed;
            try {
                parsed = new URL(href, window.location.origin);
            } catch (_) {
                return;
            }

            if (parsed.origin !== window.location.origin) {
                return;
            }
            if (!parsed.searchParams.has('page')) {
                return;
            }

            event.preventDefault();
            syncActivitiesFilterForm(form, parsed.toString());
            requestAndRender(parsed.toString(), true);
        });

        if (!window.__activitiesIndexPopstateBound) {
            window.__activitiesIndexPopstateBound = true;
            window.addEventListener('popstate', () => {
                const activeContainer = document.querySelector(ACTIVITIES_INDEX_SELECTOR);
                if (!activeContainer) {
                    return;
                }
                const activeForm = activeContainer.querySelector('[data-activities-index-form]');
                const activeResults = activeContainer.querySelector('[data-activities-index-results-wrap]');
                if (!activeForm || !activeResults) {
                    return;
                }
                syncActivitiesFilterForm(activeForm, window.location.href);
                requestAndRender(window.location.href, false);
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initActivitiesIndex(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                initActivitiesIndex(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

const ROLES_INDEX_SELECTOR = '[data-roles-index]';

function buildRolesIndexQuery(form) {
    const formData = new FormData(form);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
        const stringValue = String(value ?? '').trim();
        if (stringValue === '') {
            continue;
        }
        params.set(key, stringValue);
    }

    return params.toString();
}

function syncRolesFilterForm(form, url) {
    if (!form) {
        return;
    }
    const parsed = new URL(url, window.location.origin);
    const params = parsed.searchParams;

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
        const name = field.getAttribute('name');
        if (!name) {
            return;
        }
        const nextValue = params.get(name) || '';
        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
            field.value = nextValue;
        }
    });
}

async function fetchRolesIndex(url) {
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Roles-Ajax': '1',
        },
        credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok) {
        const error = new Error('Roles index request failed.');
        error.response = response;
        error.payload = payload;
        throw error;
    }

    return payload;
}

function initRolesIndex(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const containers = scope.matches?.(ROLES_INDEX_SELECTOR)
        ? [scope]
        : Array.from(scope.querySelectorAll(ROLES_INDEX_SELECTOR));

    containers.forEach((container) => {
        if (container.dataset.rolesIndexBound === '1') {
            return;
        }
        container.dataset.rolesIndexBound = '1';

        const form = container.querySelector('[data-roles-index-form]');
        const resultsWrap = container.querySelector('[data-roles-index-results-wrap]');
        if (!form || !resultsWrap) {
            return;
        }

        const setLoading = (isLoading) => {
            resultsWrap.classList.toggle('opacity-60', isLoading);
            resultsWrap.classList.toggle('pointer-events-none', isLoading);
            if (isLoading) {
                window.AppLoading?.showPageSpinner?.();
                return;
            }
            window.AppLoading?.hidePageSpinner?.();
        };

        const requestAndRender = async (url, updateHistory = true) => {
            setLoading(true);
            try {
                const payload = await fetchRolesIndex(url);
                if (payload?.html) {
                    resultsWrap.innerHTML = payload.html;
                }
                if (updateHistory) {
                    window.history.replaceState({ rolesIndex: true }, '', payload?.url || url);
                }
            } catch (_) {
                window.location.href = url;
            } finally {
                setLoading(false);
            }
        };

        const submitFilters = () => {
            const query = buildRolesIndexQuery(form);
            const action = form.getAttribute('action') || window.location.pathname;
            const url = query !== '' ? `${action}?${query}` : action;
            requestAndRender(url, true);
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilters();
        });

        form.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) {
                return;
            }
            if (!target.matches('[data-roles-filter-input]')) {
                return;
            }
            submitFilters();
        });

        let typingTimer = null;
        form.addEventListener('input', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.matches('[data-roles-filter-input]')) {
                return;
            }
            clearTimeout(typingTimer);
            typingTimer = window.setTimeout(() => {
                submitFilters();
            }, 350);
        });

        const resetLink = form.querySelector('[data-roles-filter-reset]');
        if (resetLink) {
            resetLink.addEventListener('click', (event) => {
                event.preventDefault();
                form.reset();
                submitFilters();
            });
        }

        resultsWrap.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) {
                return;
            }
            const href = link.getAttribute('href');
            if (!href) {
                return;
            }

            let parsed;
            try {
                parsed = new URL(href, window.location.origin);
            } catch (_) {
                return;
            }

            if (parsed.origin !== window.location.origin) {
                return;
            }
            if (!parsed.searchParams.has('page')) {
                return;
            }

            event.preventDefault();
            syncRolesFilterForm(form, parsed.toString());
            requestAndRender(parsed.toString(), true);
        });

        if (!window.__rolesIndexPopstateBound) {
            window.__rolesIndexPopstateBound = true;
            window.addEventListener('popstate', () => {
                const activeContainer = document.querySelector(ROLES_INDEX_SELECTOR);
                if (!activeContainer) {
                    return;
                }
                const activeForm = activeContainer.querySelector('[data-roles-index-form]');
                const activeResults = activeContainer.querySelector('[data-roles-index-results-wrap]');
                if (!activeForm || !activeResults) {
                    return;
                }
                syncRolesFilterForm(activeForm, window.location.href);
                requestAndRender(window.location.href, false);
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initRolesIndex(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                initRolesIndex(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

const TOURIST_ATTRACTIONS_INDEX_SELECTOR = '[data-tourist-attractions-index]';

function buildTouristAttractionsIndexQuery(form) {
    const formData = new FormData(form);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
        const stringValue = String(value ?? '').trim();
        if (stringValue === '') {
            continue;
        }
        params.set(key, stringValue);
    }

    return params.toString();
}

function syncTouristAttractionsFilterForm(form, url) {
    if (!form) {
        return;
    }
    const parsed = new URL(url, window.location.origin);
    const params = parsed.searchParams;

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
        const name = field.getAttribute('name');
        if (!name) {
            return;
        }
        const nextValue = params.get(name) || '';
        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
            field.value = nextValue;
        }
    });
}

async function fetchTouristAttractionsIndex(url) {
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Tourist-Attractions-Ajax': '1',
        },
        credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok) {
        const error = new Error('Tourist attractions index request failed.');
        error.response = response;
        error.payload = payload;
        throw error;
    }

    return payload;
}

function initTouristAttractionsIndex(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const containers = scope.matches?.(TOURIST_ATTRACTIONS_INDEX_SELECTOR)
        ? [scope]
        : Array.from(scope.querySelectorAll(TOURIST_ATTRACTIONS_INDEX_SELECTOR));

    containers.forEach((container) => {
        if (container.dataset.touristAttractionsIndexBound === '1') {
            return;
        }
        container.dataset.touristAttractionsIndexBound = '1';

        const form = container.querySelector('[data-tourist-attractions-index-form]');
        const resultsWrap = container.querySelector('[data-tourist-attractions-index-results-wrap]');
        if (!form || !resultsWrap) {
            return;
        }

        const setLoading = (isLoading) => {
            resultsWrap.classList.toggle('opacity-60', isLoading);
            resultsWrap.classList.toggle('pointer-events-none', isLoading);
            if (isLoading) {
                window.AppLoading?.showPageSpinner?.();
                return;
            }
            window.AppLoading?.hidePageSpinner?.();
        };

        const requestAndRender = async (url, updateHistory = true) => {
            setLoading(true);
            try {
                const payload = await fetchTouristAttractionsIndex(url);
                if (payload?.html) {
                    resultsWrap.innerHTML = payload.html;
                }
                if (updateHistory) {
                    window.history.replaceState({ touristAttractionsIndex: true }, '', payload?.url || url);
                }
            } catch (_) {
                window.location.href = url;
            } finally {
                setLoading(false);
            }
        };

        const submitFilters = () => {
            const query = buildTouristAttractionsIndexQuery(form);
            const action = form.getAttribute('action') || window.location.pathname;
            const url = query !== '' ? `${action}?${query}` : action;
            requestAndRender(url, true);
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilters();
        });

        form.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) {
                return;
            }
            if (!target.matches('[data-tourist-attractions-filter-input]')) {
                return;
            }
            submitFilters();
        });

        const resetLink = form.querySelector('[data-tourist-attractions-filter-reset]');
        if (resetLink) {
            resetLink.addEventListener('click', (event) => {
                event.preventDefault();
                form.reset();
                submitFilters();
            });
        }

        resultsWrap.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) {
                return;
            }
            const href = link.getAttribute('href');
            if (!href) {
                return;
            }

            let parsed;
            try {
                parsed = new URL(href, window.location.origin);
            } catch (_) {
                return;
            }

            if (parsed.origin !== window.location.origin) {
                return;
            }
            if (!parsed.searchParams.has('page')) {
                return;
            }

            event.preventDefault();
            syncTouristAttractionsFilterForm(form, parsed.toString());
            requestAndRender(parsed.toString(), true);
        });

        if (!window.__touristAttractionsIndexPopstateBound) {
            window.__touristAttractionsIndexPopstateBound = true;
            window.addEventListener('popstate', () => {
                const activeContainer = document.querySelector(TOURIST_ATTRACTIONS_INDEX_SELECTOR);
                if (!activeContainer) {
                    return;
                }
                const activeForm = activeContainer.querySelector('[data-tourist-attractions-index-form]');
                const activeResults = activeContainer.querySelector('[data-tourist-attractions-index-results-wrap]');
                if (!activeForm || !activeResults) {
                    return;
                }
                syncTouristAttractionsFilterForm(activeForm, window.location.href);
                requestAndRender(window.location.href, false);
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initTouristAttractionsIndex(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                initTouristAttractionsIndex(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

const SERVICE_FILTER_PAGE_SELECTOR = '[data-service-filter-page]';

function buildServiceFilterQuery(form) {
    const formData = new FormData(form);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
        const stringValue = String(value ?? '').trim();
        if (stringValue === '') {
            continue;
        }
        params.set(key, stringValue);
    }

    return params.toString();
}

function syncServiceFilterForm(form, url) {
    const parsed = new URL(url, window.location.origin);
    const params = parsed.searchParams;

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
        const name = field.getAttribute('name');
        if (!name) {
            return;
        }
        const nextValue = params.get(name) || '';
        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
            field.value = nextValue;
        }
    });
}

async function fetchServiceFilterPage(url) {
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Service filter request failed.');
    }

    return response.text();
}

function initServiceFilterPage(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const pages = scope.matches?.(SERVICE_FILTER_PAGE_SELECTOR)
        ? [scope]
        : Array.from(scope.querySelectorAll(SERVICE_FILTER_PAGE_SELECTOR));

    pages.forEach((page) => {
        if (page.dataset.serviceFilterBound === '1') {
            return;
        }
        page.dataset.serviceFilterBound = '1';

        const form = page.querySelector('[data-service-filter-form]');
        const resultsWrap = page.querySelector('[data-service-filter-results]');
        if (!form || !resultsWrap) {
            return;
        }

        let typingTimer = null;

        const setLoading = (isLoading) => {
            resultsWrap.classList.toggle('opacity-60', isLoading);
            resultsWrap.classList.toggle('pointer-events-none', isLoading);
            if (isLoading) {
                window.AppLoading?.showPageSpinner?.();
                return;
            }
            window.AppLoading?.hidePageSpinner?.();
        };

        const requestAndRender = async (url, pushHistory = false) => {
            setLoading(true);
            try {
                const html = await fetchServiceFilterPage(url);
                const parser = new DOMParser();
                const nextDoc = parser.parseFromString(html, 'text/html');
                const nextPage = nextDoc.querySelector(SERVICE_FILTER_PAGE_SELECTOR);
                const nextResults = nextPage?.querySelector('[data-service-filter-results]');

                if (!nextResults) {
                    window.location.href = url;
                    return;
                }

                resultsWrap.innerHTML = nextResults.innerHTML;
                if (pushHistory) {
                    window.history.pushState({ serviceFilter: true }, '', url);
                } else {
                    window.history.replaceState({ serviceFilter: true }, '', url);
                }
            } catch (_) {
                window.location.href = url;
            } finally {
                setLoading(false);
            }
        };

        const submitFilters = (pushHistory = false) => {
            const query = buildServiceFilterQuery(form);
            const action = form.getAttribute('action') || window.location.pathname;
            const url = query !== '' ? `${action}?${query}` : action;
            requestAndRender(url, pushHistory);
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilters(true);
        });

        form.addEventListener('input', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.matches('[data-service-filter-input]')) {
                return;
            }
            clearTimeout(typingTimer);
            typingTimer = window.setTimeout(() => {
                submitFilters(false);
            }, 350);
        });

        form.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) {
                return;
            }
            if (!target.matches('[data-service-filter-input]')) {
                return;
            }
            submitFilters(false);
        });

        const resetLink = form.querySelector('[data-service-filter-reset]');
        if (resetLink) {
            resetLink.addEventListener('click', (event) => {
                event.preventDefault();
                form.reset();
                submitFilters(false);
            });
        }

        resultsWrap.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) {
                return;
            }
            const href = link.getAttribute('href');
            if (!href) {
                return;
            }

            let parsed;
            try {
                parsed = new URL(href, window.location.origin);
            } catch (_) {
                return;
            }

            if (parsed.origin !== window.location.origin || !parsed.searchParams.has('page')) {
                return;
            }

            event.preventDefault();
            syncServiceFilterForm(form, parsed.toString());
            requestAndRender(parsed.toString(), true);
        });

        if (!window.__serviceFilterPopstateBound) {
            window.__serviceFilterPopstateBound = true;
            window.addEventListener('popstate', () => {
                const activePage = document.querySelector(SERVICE_FILTER_PAGE_SELECTOR);
                const activeForm = activePage?.querySelector('[data-service-filter-form]');
                const activeResults = activePage?.querySelector('[data-service-filter-results]');
                if (!activePage || !activeForm || !activeResults) {
                    return;
                }
                syncServiceFilterForm(activeForm, window.location.href);
                requestAndRender(window.location.href, false);
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initServiceFilterPage(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                initServiceFilterPage(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});


function initAdaptiveRemoveButtons(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const candidates = scope.matches?.('button, a')
        ? [scope]
        : Array.from(scope.querySelectorAll('button, a'));

    candidates.forEach((element) => {
        if (!(element instanceof HTMLButtonElement || element instanceof HTMLAnchorElement)) {
            return;
        }
        if (element.dataset.removeAdaptiveBound === '1') {
            return;
        }

        const text = String(element.textContent || '').replace(/\s+/g, ' ').trim();
        const isExplicitRemoveAction = element.hasAttribute('data-remove-row') || element.hasAttribute('data-remove-room');
        const isRemoveLabel = /^remove$/i.test(text);
        if (!isExplicitRemoveAction && !isRemoveLabel) {
            return;
        }

        const label = text === '' ? 'Remove' : text;
        element.dataset.removeAdaptiveBound = '1';
        element.classList.add('remove-adaptive-btn');
        element.innerHTML = `<span class="remove-btn-icon" aria-hidden="true">x</span><span class="remove-btn-text">${escapeHtml(label)}</span>`;

        const applyState = () => {
            const isOverflowing = (element.scrollWidth - element.clientWidth) > 2;
            element.classList.toggle('remove-btn-icon-only', isOverflowing);
        };

        applyState();

        if (window.ResizeObserver) {
            const observer = new ResizeObserver(() => applyState());
            observer.observe(element);
        }

        window.addEventListener('resize', applyState);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initAdaptiveRemoveButtons(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                initAdaptiveRemoveButtons(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

if (window.axios) {
    window.axios.interceptors.request.use((config) => {
        if (config?.showSpinner === true || config?.spinner === 'page') {
            setPageSpinnerVisible(true);
        }
        return config;
    });

    window.axios.interceptors.response.use(
        (response) => {
            if (response?.config?.showSpinner === true || response?.config?.spinner === 'page') {
                setPageSpinnerVisible(false);
            }
            return response;
        },
        (error) => {
            if (error?.config?.showSpinner === true || error?.config?.spinner === 'page') {
                setPageSpinnerVisible(false);
            }
            return Promise.reject(error);
        }
    );
}
