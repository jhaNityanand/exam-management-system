(function registerRichTextEditor(global) {
    const DEFAULT_TOOLBAR = ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'];

    function parseToolbar(rawValue) {
        if (!rawValue) {
            return DEFAULT_TOOLBAR.slice();
        }

        try {
            const parsed = JSON.parse(rawValue);
            return Array.isArray(parsed) && parsed.length ? parsed : DEFAULT_TOOLBAR.slice();
        } catch {
            return DEFAULT_TOOLBAR.slice();
        }
    }

    function toInt(value, fallback = 180) {
        const parsed = Number.parseInt(String(value ?? ''), 10);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function createFallbackAdapter(host, input) {
        input.classList.remove('hidden');
        input.classList.add('panel-input');
        host.hidden = true;

        return {
            id: input.id,
            input,
            host,
            isFallback: true,
            getData: () => input.value,
            setData: (value) => {
                input.value = String(value ?? '');
            },
            sync: () => {
                input.value = String(input.value || '');
            },
            onChange: (callback) => {
                if (typeof callback !== 'function') {
                    return;
                }
                input.addEventListener('input', () => callback(input.value));
                input.addEventListener('change', () => callback(input.value));
            },
            focus: () => input.focus(),
        };
    }

    async function createEditorAdapter(host) {
        const inputId = host.getAttribute('data-editor-input');
        const input = host.ownerDocument.getElementById(inputId);

        if (!input) {
            return null;
        }

        const toolbar = parseToolbar(host.getAttribute('data-editor-toolbar'));
        const placeholder = host.getAttribute('data-editor-placeholder') || '';
        const height = toInt(host.getAttribute('data-editor-height'), 180);

        if (typeof global.ClassicEditor === 'undefined') {
            return createFallbackAdapter(host, input);
        }

        try {
            const editor = await global.ClassicEditor.create(host, { toolbar, placeholder });
            editor.setData(input.value || '');

            const editable = editor.ui?.view?.editable?.element;
            if (editable) {
                editable.style.minHeight = `${height}px`;
            }

            editor.model.document.on('change:data', () => {
                input.value = editor.getData();
            });

            editor.editing.view.document.on('focus', () => {
                host.classList.add('is-focused');
            });

            editor.editing.view.document.on('blur', () => {
                host.classList.remove('is-focused');
            });

            return {
                id: input.id,
                input,
                host,
                editor,
                isFallback: false,
                getData: () => editor.getData(),
                setData: (value) => {
                    editor.setData(String(value ?? ''));
                    input.value = editor.getData();
                },
                sync: () => {
                    input.value = editor.getData();
                },
                onChange: (callback) => {
                    if (typeof callback !== 'function') {
                        return;
                    }
                    editor.model.document.on('change:data', () => callback(editor.getData()));
                },
                focus: () => editor.editing.view.focus(),
                destroy: () => editor.destroy(),
            };
        } catch (error) {
            console.error('Rich text editor initialization failed:', error);
            return createFallbackAdapter(host, input);
        }
    }

    async function initAll(root = document) {
        const hosts = [...root.querySelectorAll('[data-rich-editor]')];
        const registry = new Map();

        for (const host of hosts) {
            const adapter = await createEditorAdapter(host);
            if (adapter) {
                registry.set(adapter.id, adapter);
            }
        }

        return registry;
    }

    global.EmsRichTextEditor = {
        initAll,
    };
}(window));
