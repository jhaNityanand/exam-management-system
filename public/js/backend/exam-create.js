document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('exam-create-form');
    const utils = window.EmsFormUtils;

    if (!form || !utils) {
        return;
    }

    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const categorySelect = document.getElementById('category_id');
    const modeSelect = document.getElementById('exam_mode');
    const statusSelect = document.getElementById('status');
    const durationInput = document.getElementById('duration');
    const passInput = document.getElementById('pass_percentage');
    const attemptsInput = document.getElementById('max_attempts');
    const negativeInput = document.getElementById('negative_mark_per_question');
    const startInput = document.getElementById('scheduled_start');
    const endInput = document.getElementById('scheduled_end');

    const questionSearch = document.getElementById('question-bank-search');
    const questionCategoryFilter = document.getElementById('question-bank-category');
    const questionList = document.getElementById('question-bank-list');
    const questionEmpty = document.getElementById('question-bank-empty');
    const selectedCountBadge = document.getElementById('question-selected-count');
    const questionRows = [...document.querySelectorAll('[data-question-item]')];

    const summary = {
        title: document.querySelector('[data-summary-title]'),
        status: document.querySelector('[data-summary-status]'),
        category: document.querySelector('[data-summary-category]'),
        mode: document.querySelector('[data-summary-mode]'),
        duration: document.querySelector('[data-summary-duration]'),
        pass: document.querySelector('[data-summary-pass]'),
        attempts: document.querySelector('[data-summary-attempts]'),
        selected: document.querySelector('[data-summary-selected]'),
        totalMarks: document.querySelector('[data-summary-total-marks]'),
        schedule: document.querySelector('[data-summary-schedule]'),
    };

    const presetButtons = [...document.querySelectorAll('[data-exam-preset]')];
    const errorBag = utils.createErrorManager(form);
    const isDemoQuestionBank = Boolean(window.examCreateConfig?.demoQuestionBank);

    const presets = {
        practice: {
            exam_mode: 'practice',
            status: 'active',
            duration: 45,
            pass_percentage: 45,
            max_attempts: 6,
            shuffle_questions: true,
            shuffle_options: true,
        },
        screening: {
            exam_mode: 'standard',
            status: 'draft',
            duration: 60,
            pass_percentage: 60,
            max_attempts: 2,
            shuffle_questions: true,
            shuffle_options: false,
        },
        certification: {
            exam_mode: 'proctored',
            status: 'published',
            duration: 120,
            pass_percentage: 70,
            max_attempts: 1,
            shuffle_questions: true,
            shuffle_options: true,
        },
    };

    let descriptionEditor = null;

    const getSelectedText = (select, fallback) => {
        if (!select) {
            return fallback;
        }
        const option = select.options[select.selectedIndex];
        if (!option || option.value === '') {
            return fallback;
        }
        return utils.clean(option.textContent) || fallback;
    };

    const updateQuestionHighlights = () => {
        questionRows.forEach((row) => {
            const checkbox = row.querySelector('input[type="checkbox"]');
            row.classList.toggle('is-selected', Boolean(checkbox?.checked));
        });
    };

    const getSelectedQuestionStats = () => {
        let count = 0;
        let marks = 0;

        questionRows.forEach((row) => {
            const checkbox = row.querySelector('input[type="checkbox"]');
            if (!checkbox?.checked) {
                return;
            }

            count += 1;
            marks += utils.toNumber(row.dataset.questionMarks || '0') || 0;
        });

        return { count, marks };
    };

    const applyQuestionFilter = () => {
        const query = utils.clean(questionSearch?.value).toLowerCase();
        const selectedCategory = utils.clean(questionCategoryFilter?.value);

        let visibleRows = 0;
        questionRows.forEach((row) => {
            const questionText = utils.clean(row.dataset.questionText).toLowerCase();
            const questionCategory = utils.clean(row.dataset.questionCategory);
            const matchesSearch = !query || questionText.includes(query);
            const matchesCategory = !selectedCategory || questionCategory === selectedCategory;
            const visible = matchesSearch && matchesCategory;

            row.classList.toggle('is-hidden', !visible);
            if (visible) {
                visibleRows += 1;
            }
        });

        questionEmpty?.classList.toggle('hidden', visibleRows > 0);
    };

    const updateSummary = () => {
        const selectedQuestionStats = getSelectedQuestionStats();
        const formattedStart = utils.formatHumanDateTime(startInput?.value);
        const formattedEnd = utils.formatHumanDateTime(endInput?.value);

        if (summary.title) {
            summary.title.textContent = utils.clean(titleInput?.value) || 'Untitled exam';
        }
        if (summary.status) {
            summary.status.textContent = getSelectedText(statusSelect, 'Draft');
        }
        if (summary.category) {
            summary.category.textContent = getSelectedText(categorySelect, 'No category');
        }
        if (summary.mode) {
            summary.mode.textContent = getSelectedText(modeSelect, 'Standard');
        }
        if (summary.duration) {
            const value = utils.clean(durationInput?.value) || '0';
            summary.duration.textContent = `${value} min`;
        }
        if (summary.pass) {
            const value = utils.clean(passInput?.value) || '0';
            summary.pass.textContent = `${value}%`;
        }
        if (summary.attempts) {
            summary.attempts.textContent = utils.clean(attemptsInput?.value) || '1';
        }
        if (summary.selected) {
            summary.selected.textContent = String(selectedQuestionStats.count);
        }
        if (summary.totalMarks) {
            summary.totalMarks.textContent = String(selectedQuestionStats.marks);
        }
        if (summary.schedule) {
            if (!formattedStart && !formattedEnd) {
                summary.schedule.textContent = 'Not scheduled';
            } else if (formattedStart && formattedEnd) {
                summary.schedule.textContent = `${formattedStart} to ${formattedEnd}`;
            } else {
                summary.schedule.textContent = formattedStart || formattedEnd;
            }
        }
        if (selectedCountBadge) {
            selectedCountBadge.textContent = String(selectedQuestionStats.count);
        }
    };

    const setField = (id, value) => {
        const field = document.getElementById(id);
        if (!field) {
            return;
        }
        field.value = String(value);
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const setToggle = (name, checked) => {
        const toggle = form.querySelector(`input[name="${name}"]`);
        if (!toggle) {
            return;
        }
        toggle.checked = Boolean(checked);
    };

    const applyPreset = (presetName) => {
        const preset = presets[presetName];
        if (!preset) {
            return;
        }

        setField('exam_mode', preset.exam_mode);
        setField('status', preset.status);
        setField('duration', preset.duration);
        setField('pass_percentage', preset.pass_percentage);
        setField('max_attempts', preset.max_attempts);
        setToggle('shuffle_questions', preset.shuffle_questions);
        setToggle('shuffle_options', preset.shuffle_options);

        updateSummary();
    };

    const initDescriptionEditor = async () => {
        const host = document.getElementById('editor-description');
        if (!host || typeof window.ClassicEditor === 'undefined') {
            return;
        }

        try {
            descriptionEditor = await window.ClassicEditor.create(host, {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'],
                placeholder: 'Write exam overview, instructions, and context...',
            });

            descriptionEditor.setData(descriptionInput?.value || '');
        } catch (error) {
            if (descriptionInput) {
                descriptionInput.classList.remove('hidden');
                descriptionInput.classList.add('panel-input');
            }
            host.classList.add('hidden');
        }
    };

    const initEnhancedSelects = () => {
        utils.initTomSelect('#category_id', {
            placeholder: 'Search and select category...',
        });

        utils.initTomSelect('#question-bank-category', {
            allowEmptyOption: true,
            placeholder: 'All categories',
        });
    };

    const startPicker = utils.initDateTimePicker(startInput, {
        allowInput: true,
    });

    const endPicker = utils.initDateTimePicker(endInput, {
        allowInput: false,
        clickOpens: false,
    });

    if (endInput) {
        endInput.readOnly = true;
        endInput.classList.add('is-readonly');
    }

    const syncEndDateTime = utils.bindAutoEndDateTime({
        startInput,
        endInput,
        durationInput,
        onSync: (endDate) => {
            if (endPicker) {
                endPicker.setDate(endDate || null, false, 'Y-m-d H:i');
            }
            updateSummary();
        },
    });

    const validateForm = () => {
        errorBag.clearAll();
        let isValid = true;

        const title = utils.clean(titleInput?.value);
        const mode = utils.clean(modeSelect?.value);
        const status = utils.clean(statusSelect?.value);
        const duration = utils.toNumber(durationInput?.value);
        const passPercentage = utils.toNumber(passInput?.value);
        const maxAttempts = utils.toNumber(attemptsInput?.value);
        const negativeMark = utils.clean(negativeInput?.value);
        const startDate = utils.parseDateTime(startInput?.value);
        const endDate = utils.parseDateTime(endInput?.value);

        if (!title) {
            errorBag.set('title', 'Exam title is required.');
            isValid = false;
        } else if (title.length < 3) {
            errorBag.set('title', 'Exam title must be at least 3 characters.');
            isValid = false;
        }

        if (!mode) {
            errorBag.set('exam_mode', 'Exam mode is required.');
            isValid = false;
        }

        if (!status) {
            errorBag.set('status', 'Status is required.');
            isValid = false;
        }

        if (!Number.isFinite(duration) || duration < 1) {
            errorBag.set('duration', 'Duration must be at least 1 minute.');
            isValid = false;
        } else if (duration > 480) {
            errorBag.set('duration', 'Duration cannot exceed 480 minutes.');
            isValid = false;
        }

        if (!Number.isFinite(passPercentage)) {
            errorBag.set('pass_percentage', 'Pass percentage is required.');
            isValid = false;
        } else if (passPercentage < 0 || passPercentage > 100) {
            errorBag.set('pass_percentage', 'Pass percentage must be between 0 and 100.');
            isValid = false;
        }

        if (!Number.isInteger(maxAttempts) || maxAttempts < 1 || maxAttempts > 50) {
            errorBag.set('max_attempts', 'Max attempts must be a whole number between 1 and 50.');
            isValid = false;
        }

        if (negativeMark !== '') {
            const negativeValue = utils.toNumber(negativeMark);
            if (!Number.isFinite(negativeValue) || negativeValue < 0 || negativeValue > 100) {
                errorBag.set('negative_mark_per_question', 'Negative mark per question must be between 0 and 100.');
                isValid = false;
            }
        }

        if (utils.clean(startInput?.value) && !startDate) {
            errorBag.set('scheduled_start', 'Select a valid start date and time.');
            isValid = false;
        }

        if (utils.clean(endInput?.value) && !endDate) {
            errorBag.set('scheduled_end', 'End date and time is invalid.');
            isValid = false;
        }

        if (startDate && !endDate) {
            errorBag.set('scheduled_end', 'End date and time will auto-fill from start and duration.');
            isValid = false;
        }

        if (startDate && endDate && endDate < startDate) {
            errorBag.set('scheduled_end', 'End date and time cannot be earlier than start date and time.');
            isValid = false;
        }

        if (!isDemoQuestionBank && questionRows.length > 0 && getSelectedQuestionStats().count === 0) {
            errorBag.set('question_ids', 'Select at least one question for this exam.');
            isValid = false;
        }

        return isValid;
    };

    const wireLiveValidationClear = () => {
        const clearable = [
            ['title', titleInput],
            ['exam_mode', modeSelect],
            ['status', statusSelect],
            ['duration', durationInput],
            ['pass_percentage', passInput],
            ['max_attempts', attemptsInput],
            ['negative_mark_per_question', negativeInput],
            ['scheduled_start', startInput],
            ['scheduled_end', endInput],
        ];

        clearable.forEach(([key, field]) => {
            field?.addEventListener('input', () => errorBag.clear(key));
            field?.addEventListener('change', () => errorBag.clear(key));
        });

        questionList?.addEventListener('change', () => errorBag.clear('question_ids'));
    };

    questionSearch?.addEventListener('input', applyQuestionFilter);
    questionCategoryFilter?.addEventListener('change', applyQuestionFilter);

    questionList?.addEventListener('change', (event) => {
        if (!(event.target instanceof HTMLInputElement) || event.target.type !== 'checkbox') {
            return;
        }

        updateQuestionHighlights();
        updateSummary();
    });

    [titleInput, categorySelect, modeSelect, statusSelect, durationInput, passInput, attemptsInput, startInput, endInput]
        .forEach((field) => {
            field?.addEventListener('input', updateSummary);
            field?.addEventListener('change', updateSummary);
        });

    presetButtons.forEach((button) => {
        button.addEventListener('click', () => {
            applyPreset(button.dataset.examPreset);
            presetButtons.forEach((item) => item.classList.remove('is-active'));
            button.classList.add('is-active');
        });
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (descriptionEditor && descriptionInput) {
            descriptionInput.value = descriptionEditor.getData();
        }

        syncEndDateTime.sync();

        if (!validateForm()) {
            const firstError = form.querySelector('.form-field-error.is-visible');
            firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        form.submit();
    });

    initDescriptionEditor();
    initEnhancedSelects();
    wireLiveValidationClear();
    updateQuestionHighlights();
    applyQuestionFilter();
    syncEndDateTime.sync();
    updateSummary();

    if (startPicker && utils.clean(startInput?.value)) {
        startPicker.setDate(startInput.value, false, 'Y-m-d H:i');
    }
});
