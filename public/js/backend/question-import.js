(function (global) {
    'use strict';

    const MAX_FILE_BYTES = 15 * 1024 * 1024;
    const MAX_ROWS = 10000;
    const CHUNK_SIZE = 100;
    const PAGE_SIZE = 50;
    const TYPES = ['mcq', 'true_false', 'fill_blank', 'short_answer', 'long_answer'];
    const DIFFICULTIES = ['easy', 'medium', 'hard', 'very_hard'];
    const MARKS_TYPES = ['single', 'multiple'];
    const STATUSES = ['active', 'inactive', 'suspended'];
    const EDITABLE_KEYS = [
        'question', 'type', 'category', 'difficulty', 'marks_type', 'marks',
        'option_a', 'option_b', 'option_c', 'option_d',
        'correct_answer', 'correct_answers', 'explanation',
    ];

    const SAMPLE_ROWS = [
        {
            Question: 'Which language is primarily used by Laravel?',
            Type: 'mcq',
            Category: 'Development > PHP > Laravel',
            Difficulty: 'easy',
            'Marks Type': 'single',
            Marks: '1',
            'Option A': 'PHP',
            'Option B': 'Python',
            'Option C': 'Ruby',
            'Option D': 'Java',
            'Correct Answer': 'A',
            'Correct Answers': '',
            Explanation: 'Laravel is a PHP framework.',
            Reference: 'Laravel basics',
            Status: 'active',
        },
        {
            Question: 'Which items are JavaScript frameworks or libraries?',
            Type: 'mcq',
            Category: 'Development > JavaScript',
            Difficulty: 'medium',
            'Marks Type': 'single',
            Marks: '2',
            'Option A': 'React',
            'Option B': 'Vue',
            'Option C': 'Laravel',
            'Option D': 'Angular',
            'Correct Answer': '',
            'Correct Answers': 'A,B,D',
            Explanation: 'React, Vue, and Angular belong to the JavaScript ecosystem.',
            Reference: '',
            Status: 'active',
        },
        {
            Question: 'True or False: HTTP is a stateless protocol.',
            Type: 'true_false',
            Category: 'Web Fundamentals',
            Difficulty: 'easy',
            'Marks Type': 'single',
            Marks: '1',
            'Option A': '',
            'Option B': '',
            'Option C': '',
            'Option D': '',
            'Correct Answer': 'True',
            'Correct Answers': '',
            Explanation: '',
            Reference: '',
            Status: 'active',
        },
        {
            Question: 'Fill in the blank: The command used to create a Laravel migration is ____.',
            Type: 'fill_blank',
            Category: 'Development > PHP > Laravel',
            Difficulty: 'medium',
            'Marks Type': 'single',
            Marks: '2',
            'Option A': '',
            'Option B': '',
            'Option C': '',
            'Option D': '',
            'Correct Answer': 'php artisan make:migration',
            'Correct Answers': '',
            Explanation: '',
            Reference: '',
            Status: 'active',
        },
        {
            Question: 'Explain dependency injection and give one practical benefit.',
            Type: 'long_answer',
            Category: 'Software Engineering > Architecture',
            Difficulty: 'hard',
            'Marks Type': 'single',
            Marks: '5',
            'Option A': '',
            'Option B': '',
            'Option C': '',
            'Option D': '',
            'Correct Answer': 'Answers should explain supplying dependencies externally and improved testability.',
            'Correct Answers': '',
            Explanation: 'Review descriptive answers manually.',
            Reference: '',
            Status: 'active',
        },
    ];

    const HEADER_ALIASES = {
        question: ['question', 'body', 'question_text'],
        type: ['type', 'question_type'],
        category: ['category', 'category_path'],
        difficulty: ['difficulty'],
        marks_type: ['marks_type', 'mark_type'],
        marks: ['marks', 'mark'],
        option_a: ['option_a', 'optiona', 'a'],
        option_b: ['option_b', 'optionb', 'b'],
        option_c: ['option_c', 'optionc', 'c'],
        option_d: ['option_d', 'optiond', 'd'],
        option_e: ['option_e', 'optione', 'e'],
        option_f: ['option_f', 'optionf', 'f'],
        correct_answer: ['correct_answer', 'answer'],
        correct_answers: ['correct_answers', 'answers'],
        explanation: ['explanation'],
        reference: ['reference'],
        status: ['status'],
    };

    const normalizeHeader = (value) => String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const findValue = (source, aliases) => {
        for (const alias of aliases) {
            if (Object.prototype.hasOwnProperty.call(source, alias)) return source[alias];
        }
        return '';
    };

    const normalizeRow = (raw, rowNumber) => {
        const source = {};
        Object.entries(raw || {}).forEach(([key, value]) => {
            source[normalizeHeader(key)] = value == null ? '' : String(value).trim();
        });

        const row = { _row: rowNumber, _removed: false, _errors: [] };
        Object.entries(HEADER_ALIASES).forEach(([key, aliases]) => {
            row[key] = String(findValue(source, aliases) ?? '').trim();
        });
        row.type = row.type.toLowerCase().replace(/\s+/g, '_');
        row.difficulty = row.difficulty.toLowerCase().replace(/\s+/g, '_');
        row.marks_type = (row.marks_type || 'single').toLowerCase().replace(/\s+/g, '_');
        row.status = (row.status || 'active').toLowerCase();

        return row;
    };

    const validateRow = (row) => {
        const errors = [];
        if (!row.question) errors.push('Question is required');
        if (!TYPES.includes(row.type)) errors.push('Invalid type');
        if (!row.category) errors.push('Category is required');
        if (row.category && row.category.split('>').some((part) => !part.trim())) errors.push('Invalid category path');
        if (!DIFFICULTIES.includes(row.difficulty)) errors.push('Invalid difficulty');
        if (!MARKS_TYPES.includes(row.marks_type)) errors.push('Invalid marks type');
        if (!STATUSES.includes(row.status)) errors.push('Invalid status');

        const marks = String(row.marks || '').split(/[,;|]/).map((v) => Number(v.trim())).filter(Number.isFinite);
        if (!marks.length || marks.some((value) => !Number.isInteger(value) || value < 1 || value > 10)) {
            errors.push('Marks must be 1–10');
        }
        if (row.marks_type === 'single' && marks.length !== 1) errors.push('Single marks requires one value');

        if (row.type === 'mcq') {
            const options = ['A', 'B', 'C', 'D', 'E', 'F']
                .filter((letter) => row[`option_${letter.toLowerCase()}`]);
            if (options.length < 2) errors.push('MCQ needs at least two options');

            const multi = String(row.correct_answers || '').trim();
            const labels = (multi || row.correct_answer || '')
                .toUpperCase()
                .split(/[\s,;|]+/)
                .filter(Boolean);
            if (!labels.length) errors.push('Correct answer is required');
            if (labels.some((label) => !options.includes(label))) errors.push('Answer must match an option label');
        } else if (!row.correct_answer) {
            errors.push('Correct answer is required');
        }

        if (row.type === 'true_false' && !['true', 'false'].includes(row.correct_answer.toLowerCase())) {
            errors.push('Answer must be True or False');
        }

        row._errors = errors;
        return errors.length === 0;
    };

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('question-import-modal');
        const openBtn = document.getElementById('btn-import-questions');
        if (!modal || !openBtn) return;

        const fileInput = document.getElementById('question-import-file');
        const dropzone = document.getElementById('question-import-dropzone');
        const fileCard = document.getElementById('qimport-file-card');
        const review = document.getElementById('qimport-review');
        const processing = document.getElementById('qimport-processing');
        const results = document.getElementById('qimport-results');
        const tbody = document.getElementById('qimport-preview-body');
        const importBtn = document.getElementById('qimport-import-btn');
        const searchInput = document.getElementById('qimport-search');
        const filterSelect = document.getElementById('qimport-filter');
        let rows = [];
        let sourceFile = null;
        let page = 1;
        let importing = false;
        let lastFocused = null;

        const setProgress = (percent, text) => {
            document.getElementById('qimport-progress-bar').style.width = `${Math.max(0, Math.min(100, percent))}%`;
            document.getElementById('qimport-progress-text').textContent = text;
        };

        const setProcessing = (visible, title = '', detail = '') => {
            processing.hidden = !visible;
            if (title) document.getElementById('qimport-processing-title').textContent = title;
            if (detail) document.getElementById('qimport-processing-detail').textContent = detail;
        };

        const activeRows = () => rows.filter((row) => !row._removed);

        const refreshSummary = () => {
            activeRows().forEach(validateRow);
            const valid = activeRows().filter((row) => row._errors.length === 0).length;
            const invalid = activeRows().length - valid;
            document.getElementById('qimport-total').textContent = activeRows().length;
            document.getElementById('qimport-valid').textContent = valid;
            document.getElementById('qimport-invalid').textContent = invalid;
            importBtn.disabled = importing || valid === 0;
        };

        const filteredRows = () => {
            const query = searchInput.value.trim().toLowerCase();
            const filter = filterSelect.value;
            return activeRows().filter((row) => {
                const matchesQuery = !query || [
                    row.question, row.category, row.type, row.difficulty,
                ].some((value) => String(value).toLowerCase().includes(query));
                const matchesFilter = filter === 'all'
                    || (filter === 'valid' && row._errors.length === 0)
                    || (filter === 'invalid' && row._errors.length > 0);
                return matchesQuery && matchesFilter;
            });
        };

        const input = (rowIndex, key, value, wide = false) => (
            `<input class="qimport-cell-input${wide ? ' is-wide' : ''}" data-row-index="${rowIndex}" data-key="${key}" value="${escapeHtml(value)}">`
        );

        const select = (rowIndex, key, value, choices) => (
            `<select class="qimport-cell-select" data-row-index="${rowIndex}" data-key="${key}">`
            + choices.map((choice) => `<option value="${choice}"${choice === value ? ' selected' : ''}>${choice}</option>`).join('')
            + '</select>'
        );

        const render = () => {
            refreshSummary();
            const filtered = filteredRows();
            const pages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
            page = Math.min(page, pages);
            const visible = filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

            const removeIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>`;

            tbody.innerHTML = visible.map((row) => {
                const index = rows.indexOf(row);
                const status = row._errors.length
                    ? `<span class="qimport-validation is-error" title="${escapeHtml(row._errors.join('; '))}">${escapeHtml(row._errors[0])}${row._errors.length > 1 ? ` +${row._errors.length - 1}` : ''}</span>`
                    : '<span class="qimport-validation">Ready</span>';
                return `<tr>
                    <td class="qimport-col-row">${row._row}</td>
                    <td>${status}</td>
                    <td>${input(index, 'question', row.question, true)}</td>
                    <td>${input(index, 'option_a', row.option_a)}</td>
                    <td>${input(index, 'option_b', row.option_b)}</td>
                    <td>${input(index, 'option_c', row.option_c)}</td>
                    <td>${input(index, 'option_d', row.option_d)}</td>
                    <td>${input(index, 'correct_answer', row.correct_answer)}</td>
                    <td>${input(index, 'correct_answers', row.correct_answers)}</td>
                    <td>${input(index, 'explanation', row.explanation, true)}</td>
                    <td>${select(index, 'type', row.type, TYPES)}</td>
                    <td>${input(index, 'category', row.category, true)}</td>
                    <td>${select(index, 'difficulty', row.difficulty, DIFFICULTIES)}</td>
                    <td>${select(index, 'marks_type', row.marks_type, MARKS_TYPES)}</td>
                    <td>${input(index, 'marks', row.marks)}</td>
                    <td class="qimport-col-action">
                        <button type="button" class="qimport-remove-btn" data-remove-row="${index}" aria-label="Remove row ${row._row}" title="Remove row">
                            ${removeIcon}
                        </button>
                    </td>
                </tr>`;
            }).join('');

            document.getElementById('qimport-page-status').textContent = `${filtered.length} rows · Page ${page} of ${pages}`;
            document.getElementById('qimport-prev').disabled = page <= 1;
            document.getElementById('qimport-next').disabled = page >= pages;
        };

        const reset = () => {
            rows = [];
            sourceFile = null;
            page = 1;
            importing = false;
            fileInput.value = '';
            fileCard.hidden = true;
            dropzone.hidden = false;
            review.hidden = true;
            results.hidden = true;
            results.innerHTML = '';
            setProcessing(false);
            setProgress(0, 'No file selected');
            importBtn.disabled = true;
            importBtn.textContent = 'Import questions';
        };

        const open = () => {
            lastFocused = document.activeElement;
            modal.hidden = false;
            document.body.classList.add('qimport-open');
            modal.querySelector('[data-import-close]')?.focus();
        };

        const close = () => {
            if (importing) return;
            modal.hidden = true;
            document.body.classList.remove('qimport-open');
            lastFocused?.focus?.();
        };

        const fail = (message) => {
            setProcessing(false);
            setProgress(0, message);
            global.EmsToast?.error?.(message);
        };

        const processFile = async (file) => {
            if (!file) return;
            const extension = file.name.split('.').pop().toLowerCase();
            if (!['xlsx', 'csv'].includes(extension)) return fail('Choose an .xlsx or .csv file.');
            if (file.size > MAX_FILE_BYTES) return fail('The file exceeds the 15 MB limit.');
            if (!global.XLSX) return fail('Spreadsheet parser failed to load. Refresh and try again.');

            setProcessing(true, 'Reading spreadsheet…', 'Parsing happens in your browser before anything is saved.');
            setProgress(5, 'Reading file');
            results.hidden = true;

            try {
                await new Promise((resolve) => setTimeout(resolve, 30));
                const data = await file.arrayBuffer();
                setProgress(25, 'Parsing worksheet');
                const workbook = global.XLSX.read(data, { type: 'array', cellDates: false });
                const sheetName = workbook.SheetNames[0];
                if (!sheetName) throw new Error('The workbook has no worksheets.');
                const rawRows = global.XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], {
                    defval: '',
                    raw: false,
                });
                if (!rawRows.length) throw new Error('The file contains no data rows.');
                if (rawRows.length > MAX_ROWS) throw new Error(`The file contains more than ${MAX_ROWS.toLocaleString()} rows.`);

                rows = rawRows.map((row, index) => normalizeRow(row, index + 2));
                sourceFile = file;
                page = 1;
                document.getElementById('qimport-file-name').textContent = file.name;
                document.getElementById('qimport-file-meta').textContent = `${(file.size / 1024).toFixed(1)} KB · ${sheetName} · ${rows.length.toLocaleString()} rows`;
                fileCard.hidden = false;
                dropzone.hidden = true;
                review.hidden = false;
                const rules = document.getElementById('qimport-rules');
                const toggle = document.getElementById('qimport-toggle-rules');
                rules?.classList.add('is-collapsed');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.textContent = 'Show instructions';
                }
                setProcessing(false);
                setProgress(100, 'File parsed — review validation results');
                render();
            } catch (error) {
                rows = [];
                review.hidden = true;
                fail(error.message || 'Unable to parse this file.');
            }
        };

        const importRows = async () => {
            const importRows = activeRows();
            importRows.forEach(validateRow);
            const validRows = importRows.filter((row) => row._errors.length === 0);
            const invalidRows = importRows.filter((row) => row._errors.length > 0);
            if (!validRows.length || !sourceFile || importing) return;

            importing = true;
            refreshSummary();
            importBtn.textContent = 'Importing…';
            setProcessing(true, 'Importing questions…', 'The import continues in small AJAX batches. Keep this window open.');
            results.hidden = true;

            let imported = 0;
            const failures = [];
            const unrecordedFailures = [];
            const chunks = [];
            for (let i = 0; i < validRows.length; i += CHUNK_SIZE) chunks.push(validRows.slice(i, i + CHUNK_SIZE));

            let importQuestionId;
            try {
                const startData = new FormData();
                startData.append('file', sourceFile, sourceFile.name);
                startData.append('total_rows', String(importRows.length));
                startData.append('failed_rows', String(invalidRows.length));
                startData.append('initial_errors_json', JSON.stringify(invalidRows.map((row) => ({
                    row: row._row,
                    errors: row._errors,
                }))));

                const startResponse = await fetch(global.questionImportStartUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': global.questionImportCsrf,
                    },
                    body: startData,
                });
                const startPayload = await startResponse.json();
                if (!startResponse.ok) {
                    throw new Error(startPayload.message || 'The import file could not be stored.');
                }
                importQuestionId = startPayload.import_question_id;
            } catch (error) {
                importing = false;
                importBtn.textContent = 'Import questions';
                setProcessing(false);
                fail(error.message || 'Unable to start the import.');
                refreshSummary();
                return;
            }

            for (let i = 0; i < chunks.length; i++) {
                setProgress((i / chunks.length) * 100, `Processing batch ${i + 1} of ${chunks.length}`);
                try {
                    const response = await fetch(global.questionImportUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': global.questionImportCsrf,
                        },
                        body: JSON.stringify({
                            import_question_id: importQuestionId,
                            rows: chunks[i].map((row) => {
                                const payload = { _row: row._row };
                                [...EDITABLE_KEYS, 'option_e', 'option_f', 'reference', 'status'].forEach((key) => {
                                    payload[key] = row[key] || '';
                                });
                                return payload;
                            }),
                        }),
                    });
                    const payload = await response.json();
                    if (!response.ok && !Array.isArray(payload.results)) {
                        throw new Error(payload.message || 'The server rejected this batch.');
                    }
                    imported += Number(payload.imported || 0);
                    (payload.results || []).filter((item) => item.status === 'failed').forEach((item) => failures.push(item));
                } catch (error) {
                    chunks[i].forEach((row) => {
                        const failure = { row: row._row, errors: [error.message || 'Batch failed'] };
                        failures.push(failure);
                        unrecordedFailures.push(failure);
                    });
                }
            }

            try {
                const completeResponse = await fetch(`${global.questionImportsUrl}/${importQuestionId}/complete`, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': global.questionImportCsrf,
                    },
                    body: JSON.stringify({ unrecorded_errors: unrecordedFailures }),
                });
                if (!completeResponse.ok) {
                    throw new Error('The server could not finalize the import log.');
                }
            } catch (error) {
                global.EmsToast?.error?.('Questions were processed, but the import log could not be finalized.');
            }

            importing = false;
            importBtn.textContent = 'Import questions';
            importBtn.disabled = true;
            setProcessing(false);
            const failedCount = invalidRows.length + failures.length;
            setProgress(100, `Completed: ${imported} imported, ${failedCount} failed`);
            results.hidden = false;
            results.classList.toggle('has-errors', failedCount > 0);
            results.innerHTML = `<strong>${imported.toLocaleString()} questions imported.</strong>`
                + (failedCount
                    ? `<p>${failedCount} rows failed validation or import. View the import badge in the question list for details.</p>`
                    : '<p>The question bank has been refreshed.</p>');

            if (imported > 0) {
                global.EmsToast?.success?.(`${imported} questions imported.`);
                document.dispatchEvent(new CustomEvent('questions:imported', {
                    detail: { imported, importQuestionId },
                }));
            }
        };

        const downloadSample = (format) => {
            if (!global.XLSX) return fail('Spreadsheet library is unavailable.');
            const worksheet = global.XLSX.utils.json_to_sheet(SAMPLE_ROWS);
            if (format === 'csv') {
                const csv = global.XLSX.utils.sheet_to_csv(worksheet);
                const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = url;
                anchor.download = 'question-import-sample.csv';
                anchor.click();
                URL.revokeObjectURL(url);
                return;
            }
            const workbook = global.XLSX.utils.book_new();
            global.XLSX.utils.book_append_sheet(workbook, worksheet, 'Questions');
            global.XLSX.writeFile(workbook, 'question-import-sample.xlsx');
        };

        openBtn.addEventListener('click', open);
        modal.querySelectorAll('[data-import-close]').forEach((button) => button.addEventListener('click', close));
        const switchImportTab = (target) => {
            const excel = target === 'excel';
            modal.querySelectorAll('.qimport-tab[data-import-tab]').forEach((item) => {
                const active = item.dataset.importTab === target;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.getElementById('qimport-panel-excel').hidden = !excel;
            document.getElementById('qimport-panel-other').hidden = excel;
            importBtn.hidden = !excel;
        };

        modal.querySelectorAll('[data-import-tab]').forEach((tab) => {
            tab.addEventListener('click', () => switchImportTab(tab.dataset.importTab));
        });

        const rulesPanel = document.getElementById('qimport-rules');
        const rulesToggle = document.getElementById('qimport-toggle-rules');
        rulesToggle?.addEventListener('click', () => {
            const collapsed = rulesPanel.classList.toggle('is-collapsed');
            rulesToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            rulesToggle.textContent = collapsed ? 'Show instructions' : 'Hide instructions';
        });

        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
        dropzone.addEventListener('drop', (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
            processFile(event.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', () => processFile(fileInput.files[0]));
        document.getElementById('qimport-clear-file').addEventListener('click', () => {
            reset();
            fileInput.click();
        });
        document.querySelectorAll('[data-sample-format]').forEach((button) => {
            button.addEventListener('click', () => downloadSample(button.dataset.sampleFormat));
        });

        tbody.addEventListener('change', (event) => {
            const field = event.target.closest('[data-row-index][data-key]');
            if (!field) return;
            rows[Number(field.dataset.rowIndex)][field.dataset.key] = field.value.trim();
            render();
        });
        tbody.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-row]');
            if (!button) return;
            rows[Number(button.dataset.removeRow)]._removed = true;
            render();
        });
        searchInput.addEventListener('input', () => { page = 1; render(); });
        filterSelect.addEventListener('change', () => { page = 1; render(); });
        document.getElementById('qimport-prev').addEventListener('click', () => { page--; render(); });
        document.getElementById('qimport-next').addEventListener('click', () => { page++; render(); });
        importBtn.addEventListener('click', importRows);

        document.addEventListener('keydown', (event) => {
            if (modal.hidden) return;
            if (event.key === 'Escape') close();
            if (event.key === 'Tab') {
                const focusable = Array.from(modal.querySelectorAll('button:not([disabled]):not([hidden]), input:not([disabled]), select:not([disabled])'))
                    .filter((item) => item.offsetParent !== null);
                if (!focusable.length) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
    });
}(window));
