document.addEventListener('DOMContentLoaded', () => {
    const form  = document.getElementById('exam-create-form');
    const utils = window.EmsFormUtils;

    if (!form || !utils) return;

    /* ── Field refs ───────────────────────────────────────── */
    const titleInput       = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const instructionsInput= document.getElementById('instructions');
    const categorySelect   = document.getElementById('category_id');
    const modeSelect       = document.getElementById('exam_mode');
    const statusSelect     = document.getElementById('status');
    const difficultySelect = document.getElementById('difficulty_level');
    const visibilitySelect = document.getElementById('visibility');
    const durationInput    = document.getElementById('duration');
    const passInput        = document.getElementById('pass_percentage');
    const attemptsInput    = document.getElementById('max_attempts');
    const negativeInput    = document.getElementById('negative_mark_per_question');
    const startInput       = document.getElementById('scheduled_start');
    const endInput         = document.getElementById('scheduled_end');

    const questionSearch         = document.getElementById('question-bank-search');
    const questionCategoryFilter = document.getElementById('question-bank-category');
    const questionList           = document.getElementById('question-bank-list');
    const questionEmpty          = document.getElementById('question-bank-empty');
    const selectedCountBadge     = document.getElementById('question-selected-count');
    const questionRows           = [...document.querySelectorAll('[data-question-item]')];

    /* ── Aside summary refs ───────────────────────────────── */
    const summary = {
        title:       document.querySelector('[data-summary-title]'),
        status:      document.querySelector('[data-summary-status]'),
        category:    document.querySelector('[data-summary-category]'),
        mode:        document.querySelector('[data-summary-mode]'),
        difficulty:  document.querySelector('[data-summary-difficulty]'),
        visibility:  document.querySelector('[data-summary-visibility]'),
        duration:    document.querySelector('[data-summary-duration]'),
        pass:        document.querySelector('[data-summary-pass]'),
        attempts:    document.querySelector('[data-summary-attempts]'),
        selected:    document.querySelector('[data-summary-selected]'),
        totalMarks:  document.querySelector('[data-summary-total-marks]'),
        schedule:    document.querySelector('[data-summary-schedule]'),
    };

    const flagSummaryEl  = document.getElementById('flag-summary');
    const presetButtons  = [...document.querySelectorAll('[data-exam-preset]')];
    const errorBag       = utils.createErrorManager(form);
    const isDemoQBank    = Boolean(window.examCreateConfig?.demoQuestionBank);

    /* ── Toggle checkboxes ────────────────────────────────── */
    const toggleFields = [
        { name: 'shuffle_questions',      label: 'Shuffle questions' },
        { name: 'shuffle_options',        label: 'Shuffle options' },
        { name: 'show_result_immediately', label: 'Show result immediately' },
        { name: 'allow_review',           label: 'Allow answer review' },
        { name: 'certificate_enabled',    label: 'Issue certificate on pass' },
    ];

    /* ── Presets ──────────────────────────────────────────── */
    const presets = {
        practice: {
            exam_mode: 'practice', status: 'active', difficulty_level: 'beginner',
            visibility: 'public', duration: 45, pass_percentage: 45, max_attempts: 6,
            shuffle_questions: true, shuffle_options: true,
            show_result_immediately: true, allow_review: true, certificate_enabled: false,
        },
        screening: {
            exam_mode: 'standard', status: 'draft', difficulty_level: 'intermediate',
            visibility: 'invite', duration: 60, pass_percentage: 60, max_attempts: 2,
            shuffle_questions: true, shuffle_options: false,
            show_result_immediately: false, allow_review: true, certificate_enabled: false,
        },
        certification: {
            exam_mode: 'proctored', status: 'published', difficulty_level: 'advanced',
            visibility: 'invite', duration: 120, pass_percentage: 70, max_attempts: 1,
            shuffle_questions: true, shuffle_options: true,
            show_result_immediately: false, allow_review: false, certificate_enabled: true,
        },
    };

    let descriptionEditor  = null;
    let instructionsEditor = null;

    /* ── Helpers ──────────────────────────────────────────── */
    const getSelectedText = (select, fallback) => {
        if (!select) return fallback;
        const opt = select.options[select.selectedIndex];
        if (!opt || opt.value === '') return fallback;
        return utils.clean(opt.textContent) || fallback;
    };

    const updateQuestionHighlights = () => {
        questionRows.forEach((row) => {
            const cb = row.querySelector('input[type="checkbox"]');
            row.classList.toggle('is-selected', Boolean(cb?.checked));
        });
    };

    const getSelectedQuestionStats = () => {
        let count = 0, marks = 0;
        questionRows.forEach((row) => {
            const cb = row.querySelector('input[type="checkbox"]');
            if (!cb?.checked) return;
            count++;
            marks += utils.toNumber(row.dataset.questionMarks || '0') || 0;
        });
        return { count, marks };
    };

    const applyQuestionFilter = () => {
        const q    = utils.clean(questionSearch?.value).toLowerCase();
        const cat  = utils.clean(questionCategoryFilter?.value);
        let visible = 0;
        questionRows.forEach((row) => {
            const text     = utils.clean(row.dataset.questionText).toLowerCase();
            const rowCat   = utils.clean(row.dataset.questionCategory);
            const show     = (!q || text.includes(q)) && (!cat || rowCat === cat);
            row.classList.toggle('is-hidden', !show);
            if (show) visible++;
        });
        questionEmpty?.classList.toggle('hidden', visible > 0);
    };

    /* ── Flag summary in aside ────────────────────────────── */
    const renderFlagSummary = () => {
        if (!flagSummaryEl) return;
        flagSummaryEl.innerHTML = toggleFields.map(({ name, label }) => {
            const checkbox = form.querySelector(`input[name="${name}"]`);
            const isOn     = Boolean(checkbox?.checked);
            return `
                <div class="exam-flag-item">
                    <span class="exam-flag-dot ${isOn ? 'is-on' : 'is-off'}"></span>
                    <span>${label}</span>
                </div>
            `;
        }).join('');
    };

    /* ── Live summary ─────────────────────────────────────── */
    const updateSummary = () => {
        const stats          = getSelectedQuestionStats();
        const formattedStart = utils.formatHumanDateTime(startInput?.value);
        const formattedEnd   = utils.formatHumanDateTime(endInput?.value);

        if (summary.title)      summary.title.textContent      = utils.clean(titleInput?.value) || 'Untitled exam';
        if (summary.status)     summary.status.textContent     = getSelectedText(statusSelect, 'Draft');
        if (summary.category)   summary.category.textContent   = getSelectedText(categorySelect, 'No category');
        if (summary.mode)       summary.mode.textContent       = getSelectedText(modeSelect, 'Standard');
        if (summary.difficulty) summary.difficulty.textContent = getSelectedText(difficultySelect, 'Intermediate');
        if (summary.visibility) summary.visibility.textContent = getSelectedText(visibilitySelect, 'Public');
        if (summary.duration)   summary.duration.textContent   = `${utils.clean(durationInput?.value) || 0} min`;
        if (summary.pass)       summary.pass.textContent       = `${utils.clean(passInput?.value) || 0}%`;
        if (summary.attempts)   summary.attempts.textContent   = utils.clean(attemptsInput?.value) || '1';
        if (summary.selected)   summary.selected.textContent   = String(stats.count);
        if (summary.totalMarks) summary.totalMarks.textContent = String(stats.marks);

        if (summary.schedule) {
            if (!formattedStart && !formattedEnd) summary.schedule.textContent = 'Not scheduled';
            else if (formattedStart && formattedEnd) summary.schedule.textContent = `${formattedStart} → ${formattedEnd}`;
            else summary.schedule.textContent = formattedStart || formattedEnd;
        }

        if (selectedCountBadge) selectedCountBadge.textContent = String(stats.count);

        renderFlagSummary();
    };

    /* ── Preset helpers ───────────────────────────────────── */
    const setField = (id, value) => {
        const f = document.getElementById(id);
        if (!f) return;
        f.value = String(value);
        f.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const setToggle = (name, checked) => {
        const t = form.querySelector(`input[name="${name}"]`);
        if (!t) return;
        t.checked = Boolean(checked);
        t.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const applyPreset = (name) => {
        const preset = presets[name];
        if (!preset) return;
        setField('exam_mode',        preset.exam_mode);
        setField('status',           preset.status);
        setField('difficulty_level', preset.difficulty_level);
        setField('visibility',       preset.visibility);
        setField('duration',         preset.duration);
        setField('pass_percentage',  preset.pass_percentage);
        setField('max_attempts',     preset.max_attempts);
        setToggle('shuffle_questions',       preset.shuffle_questions);
        setToggle('shuffle_options',         preset.shuffle_options);
        setToggle('show_result_immediately', preset.show_result_immediately);
        setToggle('allow_review',            preset.allow_review);
        setToggle('certificate_enabled',     preset.certificate_enabled);
        updateSummary();
    };

    /* ── Editors ──────────────────────────────────────────── */
    const initEditors = async () => {
        const initOne = async (containerId, hiddenInput, placeholder) => {
            const host = document.getElementById(containerId);
            if (!host || typeof window.ClassicEditor === 'undefined') return null;
            try {
                const editor = await window.ClassicEditor.create(host, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'],
                    placeholder,
                });
                editor.setData(hiddenInput?.value || '');
                return editor;
            } catch {
                if (hiddenInput) { hiddenInput.classList.remove('hidden'); hiddenInput.classList.add('panel-input'); }
                host.classList.add('hidden');
                return null;
            }
        };

        descriptionEditor  = await initOne('editor-description',  descriptionInput,   'Write exam overview, instructions, and context…');
        instructionsEditor = await initOne('editor-instructions',  instructionsInput,  'Write guidelines shown to the candidate before the exam begins…');
    };

    /* ── TomSelect & DatePickers ──────────────────────────── */
    const initEnhancedSelects = () => {
        utils.initTomSelect('#category_id', { placeholder: 'Search for a category…' });
        utils.initTomSelect('#question-bank-category', { allowEmptyOption: true, placeholder: 'All categories' });
    };

    const startPicker = utils.initDateTimePicker(startInput, { allowInput: true });
    const endPicker   = utils.initDateTimePicker(endInput,   { allowInput: false, clickOpens: false });

    if (endInput) { endInput.readOnly = true; endInput.classList.add('is-readonly'); }

    const syncEnd = utils.bindAutoEndDateTime({
        startInput, endInput, durationInput,
        onSync: (endDate) => {
            if (endPicker) endPicker.setDate(endDate || null, false, 'Y-m-d H:i');
            updateSummary();
        },
    });

    /* ── Validation ───────────────────────────────────────── */
    const validateForm = () => {
        errorBag.clearAll();
        let ok = true;

        const title       = utils.clean(titleInput?.value);
        const mode        = utils.clean(modeSelect?.value);
        const status      = utils.clean(statusSelect?.value);
        const duration    = utils.toNumber(durationInput?.value);
        const passPercent = utils.toNumber(passInput?.value);
        const maxAttempts = utils.toNumber(attemptsInput?.value);
        const negMark     = utils.clean(negativeInput?.value);
        const startDate   = utils.parseDateTime(startInput?.value);
        const endDate     = utils.parseDateTime(endInput?.value);

        if (!title)               { errorBag.set('title', 'Exam title is required.');                         ok = false; }
        else if (title.length < 3) { errorBag.set('title', 'Exam title must be at least 3 characters.');      ok = false; }
        if (!mode)                { errorBag.set('exam_mode', 'Exam mode is required.');                      ok = false; }
        if (!status)              { errorBag.set('status', 'Status is required.');                            ok = false; }
        if (!Number.isFinite(duration) || duration < 1)   { errorBag.set('duration', 'Duration must be at least 1 minute.'); ok = false; }
        else if (duration > 480)  { errorBag.set('duration', 'Duration cannot exceed 480 minutes.');          ok = false; }
        if (!Number.isFinite(passPercent) || passPercent < 0 || passPercent > 100)
            { errorBag.set('pass_percentage', 'Pass percentage must be between 0 and 100.'); ok = false; }
        if (!Number.isInteger(maxAttempts) || maxAttempts < 1 || maxAttempts > 50)
            { errorBag.set('max_attempts', 'Max attempts must be a whole number between 1 and 50.'); ok = false; }
        if (negMark !== '') {
            const nv = utils.toNumber(negMark);
            if (!Number.isFinite(nv) || nv < 0 || nv > 100)
                { errorBag.set('negative_mark_per_question', 'Must be a value between 0 and 100.'); ok = false; }
        }
        if (utils.clean(startInput?.value) && !startDate) { errorBag.set('scheduled_start', 'Select a valid start date and time.'); ok = false; }
        if (utils.clean(endInput?.value)   && !endDate)   { errorBag.set('scheduled_end',   'End date is invalid.'); ok = false; }
        if (startDate && !endDate)    { errorBag.set('scheduled_end', 'End date will auto-fill from start and duration.'); ok = false; }
        if (startDate && endDate && endDate < startDate)
            { errorBag.set('scheduled_end', 'End date cannot be earlier than start date.'); ok = false; }
        if (!isDemoQBank && questionRows.length > 0 && getSelectedQuestionStats().count === 0)
            { errorBag.set('question_ids', 'Select at least one question for this exam.'); ok = false; }

        return ok;
    };

    const wireLiveValidation = () => {
        [
            ['title',                    titleInput],
            ['exam_mode',                modeSelect],
            ['status',                   statusSelect],
            ['duration',                 durationInput],
            ['pass_percentage',          passInput],
            ['max_attempts',             attemptsInput],
            ['negative_mark_per_question', negativeInput],
            ['scheduled_start',          startInput],
            ['scheduled_end',            endInput],
        ].forEach(([key, field]) => {
            field?.addEventListener('input',  () => errorBag.clear(key));
            field?.addEventListener('change', () => errorBag.clear(key));
        });
        questionList?.addEventListener('change', () => errorBag.clear('question_ids'));
    };

    /* ── Wire events ──────────────────────────────────────── */
    questionSearch?.addEventListener('input', applyQuestionFilter);
    questionCategoryFilter?.addEventListener('change', applyQuestionFilter);

    questionList?.addEventListener('change', (e) => {
        if (e.target?.type !== 'checkbox') return;
        updateQuestionHighlights();
        updateSummary();
    });

    // Toggle checkboxes → live flag update
    toggleFields.forEach(({ name }) => {
        const checkbox = form.querySelector(`input[name="${name}"]`);
        checkbox?.addEventListener('change', renderFlagSummary);
    });

    [titleInput, categorySelect, modeSelect, statusSelect, difficultySelect, visibilitySelect, durationInput, passInput, attemptsInput, startInput, endInput]
        .forEach((field) => {
            field?.addEventListener('input',  updateSummary);
            field?.addEventListener('change', updateSummary);
        });

    presetButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            applyPreset(btn.dataset.examPreset);
            presetButtons.forEach((b) => b.classList.remove('is-active'));
            btn.classList.add('is-active');
        });
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // Flush rich editors into hidden textareas
        if (descriptionEditor  && descriptionInput)  descriptionInput.value  = descriptionEditor.getData();
        if (instructionsEditor && instructionsInput)  instructionsInput.value = instructionsEditor.getData();

        syncEnd.sync();

        if (!validateForm()) {
            form.querySelector('.form-field-error.is-visible')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        form.submit();
    });

    /* ── Boot ─────────────────────────────────────────────── */
    initEditors();
    initEnhancedSelects();
    wireLiveValidation();
    updateQuestionHighlights();
    applyQuestionFilter();
    syncEnd.sync();
    updateSummary();

    if (startPicker && utils.clean(startInput?.value)) {
        startPicker.setDate(startInput.value, false, 'Y-m-d H:i');
    }
});
