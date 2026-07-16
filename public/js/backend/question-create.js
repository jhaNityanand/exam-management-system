document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('type');
    const sections = {
        mcq: document.getElementById('section-mcq'),
        true_false: document.getElementById('section-true-false'),
        short_answer: document.getElementById('section-short-answer'),
        long_answer: document.getElementById('section-short-answer'),
        fill_blank: document.getElementById('section-short-answer'),
    };

    const isTextAnswerType = (type) => ['short_answer', 'long_answer', 'fill_blank'].includes(type);

    const allowsMultiple = document.getElementById('allows_multiple');
    const optionsContainer = document.getElementById('options-container');
    const btnAddOption = document.getElementById('btn-add-option');
    const optionTemplate = document.getElementById('option-template');
    const form = document.getElementById('question-form');

    // Marks UI Elements
    const marksTypeSelect = document.getElementById('marks_type');
    const singleInput = document.getElementById('marks');
    const multipleInput = document.getElementById('marks_list');
    const pillButtons = document.querySelectorAll('.marks-pill-btn');

    let optionCount = 0;
    const optionEditors = {}; // EmsRichTextEditor adapters keyed by option index

    let bodyEditor = null;
    let explanationEditor = null;
    let saAnswerEditor = null;

    const getEditorData = (adapter, fallbackId) => {
        if (adapter && typeof adapter.getData === 'function') {
            return String(adapter.getData() || '');
        }
        const el = fallbackId ? document.getElementById(fallbackId) : null;
        return el ? String(el.value || '') : '';
    };

    // Helper: Strip HTML for length verification
    function stripHtml(html) {
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    // Helper: Show validation errors
    const showError = (field, message) => {
        field.classList.add('is-invalid');
        let errorEl = field.parentElement?.querySelector('.qcat-field-error');
        if (!errorEl) {
            errorEl = document.createElement('p');
            errorEl.className = 'qcat-field-error';
            field.after(errorEl);
        }
        errorEl.textContent = message;
        errorEl.classList.add('is-visible');
    };

    // Helper: Clear validation errors
    const clearError = (field) => {
        field.classList.remove('is-invalid');
        const errorEl = field.parentElement?.querySelector('.qcat-field-error');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.remove('is-visible');
        }
    };

    // Clear errors automatically when input changes
    document.querySelectorAll('.panel-input, select, textarea').forEach((field) => {
        field.addEventListener('input', () => clearError(field));
        field.addEventListener('change', () => clearError(field));
    });

    // ── Rich text editor initialization (central EmsRichTextEditor / TinyMCE) ─
    async function initQuestionEditors() {
        if (!window.EmsRichTextEditor?.initAll) {
            return;
        }

        await window.EmsRichTextEditor.initAll(document);
        bodyEditor = window.EmsRichTextEditor.get('body');
        explanationEditor = window.EmsRichTextEditor.get('explanation');
        saAnswerEditor = window.EmsRichTextEditor.get('sa_answer');

        bodyEditor?.onChange(() => {
            if (stripHtml(bodyEditor.getData()).trim() !== '') {
                clearError(document.getElementById('body')?.closest('.ems-rich-editor') || document.getElementById('body'));
                const err = document.getElementById('err-body');
                if (err) {
                    err.textContent = '';
                    err.classList.remove('is-visible');
                }
            }
        });

        saAnswerEditor?.onChange(() => {
            if (stripHtml(saAnswerEditor.getData()).trim() !== '') {
                const err = document.getElementById('err-sa');
                if (err) {
                    err.textContent = '';
                    err.classList.remove('is-visible');
                }
            }
        });
    }

    initQuestionEditors().catch((error) => console.error(error));


    // ── Question Type Change ───────────────────────────────────────────────
    const updateSections = () => {
        const val = typeSelect.value;
        Object.values(sections).forEach(s => s.classList.add('hidden'));
        if (sections[val]) {
            sections[val].classList.remove('hidden');
        }
    };
    typeSelect.addEventListener('change', updateSections);
    updateSections();


    // ── Interactive Marks Selection UI ────────────────────────────────────
    const activeClasses = ['bg-indigo-600', 'text-white', 'border-indigo-600', 'dark:bg-indigo-500', 'dark:border-indigo-500', 'shadow-sm'];
    const inactiveClasses = ['bg-white', 'text-slate-700', 'border-slate-200', 'dark:bg-slate-950', 'dark:text-slate-300', 'dark:border-slate-800', 'hover:bg-slate-50', 'dark:hover:bg-slate-900'];

    const syncPillsUI = () => {
        const isMultiple = marksTypeSelect.value === 'multiple';
        
        pillButtons.forEach(btn => {
            const val = parseInt(btn.dataset.value, 10);
            let isActive = false;

            if (isMultiple) {
                const option = multipleInput.querySelector(`option[value="${val}"]`);
                isActive = option ? option.selected : false;
            } else {
                isActive = parseInt(singleInput.value, 10) === val;
            }

            if (isActive) {
                btn.classList.remove(...inactiveClasses);
                btn.classList.add(...activeClasses);
            } else {
                btn.classList.remove(...activeClasses);
                btn.classList.add(...inactiveClasses);
            }
        });
    };

    const handlePillClick = (btn) => {
        const val = parseInt(btn.dataset.value, 10);
        const isMultiple = marksTypeSelect.value === 'multiple';

        clearError(document.getElementById('marks-pills-container'));

        if (isMultiple) {
            const option = multipleInput.querySelector(`option[value="${val}"]`);
            if (option) {
                option.selected = !option.selected;
            }
        } else {
            singleInput.value = val;
        }

        syncPillsUI();
    };

    pillButtons.forEach(btn => {
        btn.addEventListener('click', () => handlePillClick(btn));
    });

    marksTypeSelect.addEventListener('change', () => {
        const isMultiple = marksTypeSelect.value === 'multiple';
        if (isMultiple) {
            // Sync current single value to multiple options
            const currentSingleVal = parseInt(singleInput.value, 10);
            Array.from(multipleInput.options).forEach(opt => {
                opt.selected = parseInt(opt.value, 10) === currentSingleVal;
            });
        } else {
            // Find first selected option in multiple list and set as single value
            const firstSelected = Array.from(multipleInput.selectedOptions)[0];
            singleInput.value = firstSelected ? firstSelected.value : 1;
            
            // Clear multiple select
            Array.from(multipleInput.options).forEach(opt => opt.selected = false);
        }
        syncPillsUI();
    });

    // Initial sync
    syncPillsUI();


    // ── MCQ Options Operations ─────────────────────────────────────────────
    const addOption = (text = '', isCorrect = false) => {
        const idx = optionCount++;
        const clone = optionTemplate.content.cloneNode(true);
        const item = clone.querySelector('.option-item');
        item.dataset.index = idx;

        // Setup correct answer input (checkbox or radio)
        const isMulti = allowsMultiple.checked;
        const input = item.querySelector('.correct-answer-input');
        input.name = isMulti ? `correct_answers[]` : `correct_answer`;
        input.type = isMulti ? 'checkbox' : 'radio';
        input.value = `option_${idx}`;
        input.checked = isCorrect;

        // Clear error when clicked
        input.addEventListener('change', () => {
            clearError(optionsContainer);
        });

        // Insert into DOM
        optionsContainer.appendChild(clone);

        const textarea = item.querySelector('[data-option-editor]');
        if (textarea) {
            if (!textarea.id) {
                textarea.id = `option_editor_${idx}_${Date.now()}`;
            }
            const mountOption = async () => {
                if (!window.EmsRichTextEditor?.mount) {
                    textarea.value = text || '';
                    optionEditors[idx] = {
                        getData: () => textarea.value,
                        setData: (value) => { textarea.value = String(value || ''); },
                        destroy: () => {},
                    };
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'ems-rich-editor ems-rich-editor--compact';
                wrapper.setAttribute('data-ems-rich-editor', '');
                wrapper.setAttribute('data-editor-input', textarea.id);
                wrapper.setAttribute('data-editor-height', '140');
                wrapper.setAttribute('data-editor-preset', 'compact');
                wrapper.setAttribute('data-editor-mode', 'compact');
                wrapper.setAttribute(
                    'data-editor-upload-url',
                    document.querySelector('[data-editor-upload-url]')?.getAttribute('data-editor-upload-url') || '/admin/editor/media'
                );
                wrapper.setAttribute(
                    'data-editor-cdn-base',
                    document.querySelector('[data-editor-cdn-base]')?.getAttribute('data-editor-cdn-base') || 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1'
                );
                textarea.parentNode.insertBefore(wrapper, textarea);
                wrapper.appendChild(textarea);

                const adapter = await window.EmsRichTextEditor.mount(textarea, {
                    wrapper,
                    height: 140,
                    mode: 'compact',
                    preset: 'compact',
                    menubar: false,
                    placeholder: 'Enter option text…',
                });
                optionEditors[idx] = adapter;
                if (text) {
                    adapter.setData(text);
                }
                adapter.onChange?.(() => clearError(optionsContainer));
            };

            mountOption().catch((error) => console.error(error));
        }
    };

    // Add Option button listener
    btnAddOption.addEventListener('click', () => addOption());

    // Remove Option listener
    optionsContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remove-option');
        if (btn) {
            const item = btn.closest('.option-item');
            const idx = item.dataset.index;
            if (optionEditors[idx]) {
                optionEditors[idx].destroy?.();
                delete optionEditors[idx];
            }
            item.remove();
            clearError(optionsContainer);
        }
    });

    // Allows Multiple answers toggle
    allowsMultiple.addEventListener('change', (e) => {
        const inputs = optionsContainer.querySelectorAll('.correct-answer-input');
        const checkedIndices = [];

        // Save checked indices before conversion
        inputs.forEach((input, index) => {
            if (input.checked) {
                checkedIndices.push(index);
            }
        });

        // Re-generate correct input fields
        inputs.forEach((input, index) => {
            input.type = e.target.checked ? 'checkbox' : 'radio';
            input.name = e.target.checked ? `correct_answers[]` : `correct_answer`;
            // Keep checked state for checked index (radios can only keep 1 checked)
            if (e.target.checked) {
                input.checked = checkedIndices.includes(index);
            } else {
                input.checked = checkedIndices.length > 0 && checkedIndices[0] === index;
            }
        });
    });


    // ── Load Existing Question Data (Edit mode) ───────────────────────────
    if (window.existingQuestion) {
        const eq = window.existingQuestion;
        if (eq.type === 'mcq') {
            optionsContainer.innerHTML = ''; // clear initial empty ones
            const options = eq.options || [];
            options.forEach((opt) => {
                const optText = opt.text || '';
                let isCorrect = false;
                if (eq.allows_multiple) {
                    isCorrect = Array.isArray(eq.correct_answers) && eq.correct_answers.includes(optText);
                } else {
                    isCorrect = eq.correct_answer === optText;
                }
                addOption(optText, isCorrect);
            });
        }
        
        // Load marks list for edit view
        if (eq.marks_type === 'multiple' && Array.isArray(eq.marks_list)) {
            Array.from(multipleInput.options).forEach(opt => {
                opt.selected = eq.marks_list.includes(parseInt(opt.value, 10));
            });
        }
        syncPillsUI();
    } else {
        // Create mode: add first two empty options by default
        if (typeSelect.value === 'mcq') {
            addOption();
            addOption();
        }
    }


    // SEO and AI toggle behaviors are managed by seo-manager.js


    // ── Form Submission with JS validations ───────────────────────────────
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        let isValid = true;

        // Hide all current errors
        document.querySelectorAll('.qcat-field-error').forEach(el => {
            el.textContent = '';
            el.classList.remove('is-visible');
        });
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // 1. Category validation
        const categoryIdEl = document.getElementById('category_id');
        if (!categoryIdEl.value) {
            showError(categoryIdEl, 'Please select a category.');
            isValid = false;
        }

        // 2. Question Type validation
        const typeEl = document.getElementById('type');
        if (!typeEl.value) {
            showError(typeEl, 'Please select a question type.');
            isValid = false;
        }

        // 3. Difficulty validation
        const diffEl = document.getElementById('difficulty');
        if (!diffEl.value) {
            showError(diffEl, 'Please select a difficulty level.');
            isValid = false;
        }

        // 4. Marks Type and Value validation
        const marksTypeVal = marksTypeSelect.value;
        const marksPillsContainer = document.getElementById('marks-pills-container');

        if (marksTypeVal === 'single') {
            const marksVal = parseInt(singleInput.value, 10);
            if (isNaN(marksVal) || marksVal < 1 || marksVal > 10) {
                showError(marksPillsContainer, 'Please select a mark value (1 to 10).');
                isValid = false;
            }
        } else if (marksTypeVal === 'multiple') {
            const selectedMarks = Array.from(multipleInput.selectedOptions).map(o => o.value);
            if (selectedMarks.length === 0) {
                showError(marksPillsContainer, 'Please select at least one mark value.');
                isValid = false;
            }
        }

        // 5. Question Body validation
        window.EmsRichTextEditor?.syncAll?.();
        const qBodyHtml = getEditorData(bodyEditor || window.EmsRichTextEditor?.get('body'), 'body').trim();
        const qBodyText = stripHtml(qBodyHtml).trim();
        if (qBodyText === '') {
            showError(document.getElementById('body')?.closest('.ems-rich-editor') || document.getElementById('err-body'), 'Question content cannot be empty.');
            isValid = false;
        }
        document.getElementById('body').value = qBodyHtml;

        // 6. Type-specific validations
        const type = typeSelect.value;
        if (type === 'mcq') {
            const items = optionsContainer.querySelectorAll('.option-item');
            let hasValidOptionsCount = items.length >= 2;
            let hasCorrectSelected = false;
            let optionsValid = true;

            Array.from(items).forEach((item) => {
                const idx = item.dataset.index;
                const htmlContent = optionEditors[idx] ? optionEditors[idx].getData() : '';
                if (stripHtml(htmlContent).trim() === '') {
                    optionsValid = false; // At least one option is empty
                }
                const correctInput = item.querySelector('.correct-answer-input');
                if (correctInput.checked) {
                    hasCorrectSelected = true;
                }
            });

            if (!hasValidOptionsCount || !hasCorrectSelected || !optionsValid) {
                if (!optionsValid) {
                    showError(optionsContainer, 'All options must have content.');
                } else if (!hasValidOptionsCount) {
                    showError(optionsContainer, 'Please provide at least 2 options.');
                } else {
                    showError(optionsContainer, 'Please select at least one correct answer.');
                }
                isValid = false;
            }
        } else if (type === 'true_false') {
            const checked = document.querySelector('input[name="tf_answer"]:checked');
            if (!checked) {
                showError(document.querySelector('input[name="tf_answer"]').closest('.flex'), 'You must select True or False.');
                isValid = false;
            }
        } else if (isTextAnswerType(type)) {
            const saHtml = getEditorData(saAnswerEditor || window.EmsRichTextEditor?.get('sa_answer'), 'sa_answer').trim();
            const saText = stripHtml(saHtml).trim();
            if (saText === '') {
                showError(document.getElementById('sa_answer')?.closest('.ems-rich-editor') || document.getElementById('err-sa'), 'Reference answer is required.');
                isValid = false;
            }
            document.getElementById('sa_answer').value = saHtml;
        }

        // 7. Canonical URL validation
        const canonicalFld = document.getElementById('meta-canonical');
        if (canonicalFld && canonicalFld.value.trim()) {
            try {
                new URL(canonicalFld.value.trim());
            } catch {
                showError(canonicalFld, 'Please enter a valid URL (e.g. https://example.com).');
                isValid = false;
            }
        }

        if (!isValid) {
            // Scroll to the first invalid field
            const firstInvalid = form.querySelector('.is-invalid, .ems-rich-editor.is-invalid, .ts-control.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // --- All Validations Passed ---
        window.EmsRichTextEditor?.syncAll?.();
        document.getElementById('explanation').value = getEditorData(
            explanationEditor || window.EmsRichTextEditor?.get('explanation'),
            'explanation'
        );

        // Clean up previous hidden inputs for options
        document.querySelectorAll('.appended-option-input').forEach(el => el.remove());

        if (type === 'mcq') {
            const items = optionsContainer.querySelectorAll('.option-item');
            Array.from(items).forEach((item, index) => {
                const idx = item.dataset.index;
                const htmlContent = optionEditors[idx] ? optionEditors[idx].getData() : '';

                const hiddenTextInput = document.createElement('input');
                hiddenTextInput.type = 'hidden';
                hiddenTextInput.name = `options[${index}][text]`;
                hiddenTextInput.value = htmlContent;
                hiddenTextInput.classList.add('appended-option-input');
                form.appendChild(hiddenTextInput);

                const correctInput = item.querySelector('.correct-answer-input');
                if (correctInput.checked) {
                    const hiddenCorrect = document.createElement('input');
                    hiddenCorrect.type = 'hidden';
                    hiddenCorrect.name = allowsMultiple.checked ? `correct_answers[]` : `correct_answer`;
                    hiddenCorrect.value = htmlContent;
                    hiddenCorrect.classList.add('appended-option-input');
                    form.appendChild(hiddenCorrect);
                }
            });
            // Disable original visible inputs so they don't submit "option_X"
            optionsContainer.querySelectorAll('.correct-answer-input').forEach(el => el.disabled = true);

        } else if (type === 'true_false') {
            const checked = document.querySelector('input[name="tf_answer"]:checked');
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'correct_answer';
            hidden.value = checked.value;
            hidden.classList.add('appended-option-input');
            form.appendChild(hidden);
        } else if (isTextAnswerType(type)) {
            const html = document.getElementById('sa_answer').value;
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'correct_answer';
            hidden.value = html;
            hidden.classList.add('appended-option-input');
            form.appendChild(hidden);
        }

        // Premium Loading State: Disable button and show loader text
        const submitBtn = document.getElementById('btn-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
            `;
        }

        form.submit();
    });
});
