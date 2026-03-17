import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const WYSIWYG_SELECTOR = 'textarea:not([data-wysiwyg-initialized]):not([data-wysiwyg="false"])';
const HTML_TAG_PATTERN = /<[^>]+>/;

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

document.addEventListener('DOMContentLoaded', () => {
    initTailwindWysiwyg(document);

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
