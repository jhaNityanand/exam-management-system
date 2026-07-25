(function (global) {
    'use strict';

    const MAX_FILE_BYTES = 15 * 1024 * 1024;
    const MAX_ROWS = 10000;
    const CHUNK_SIZE = 100;
    const PAGE_SIZE = 50;
    const TYPES = ['mcq', 'true_false', 'fill_blank', 'short_answer', 'long_answer'];
    const DEFAULT_DIFFICULTY = 'medium';
    const DEFAULT_MARKS_TYPE = 'single';
    const DEFAULT_MARKS = '1';
    const DEFAULT_STATUS = 'active';

    const EDITABLE_KEYS = [
        'question', 'option_a', 'option_b', 'option_c', 'option_d',
        'correct_options', 'explanation', 'marks', 'category', 'type', 'reference',
    ];

    const SAMPLE_HEADERS = [
        'Question',
        'Option A',
        'Option B',
        'Option C',
        'Option D',
        'Correct Option(s)',
        'Explanation',
        'Marks',
        'Category',
        'Question Type',
        'Reference',
    ];

    const SAMPLE_ROWS = [
        {
            Question: 'Which language is primarily used by Laravel?',
            'Option A': 'PHP',
            'Option B': 'Python',
            'Option C': 'Ruby',
            'Option D': 'Java',
            'Correct Option(s)': 'A',
            Explanation: 'Laravel is a PHP framework.',
            Marks: '1',
            Category: 'Development > PHP > Laravel',
            'Question Type': 'mcq',
            Reference: 'Laravel basics',
        },
        {
            Question: 'Which items are JavaScript frameworks or libraries?',
            'Option A': 'React',
            'Option B': 'Vue',
            'Option C': 'Laravel',
            'Option D': 'Angular',
            'Correct Option(s)': 'A,B,D',
            Explanation: 'React, Vue, and Angular belong to the JavaScript ecosystem.',
            Marks: '2',
            Category: 'Development > JavaScript',
            'Question Type': 'mcq',
            Reference: '',
        },
        {
            Question: 'True or False: HTTP is a stateless protocol.',
            'Option A': '',
            'Option B': '',
            'Option C': '',
            'Option D': '',
            'Correct Option(s)': 'True',
            Explanation: 'Each HTTP request is independent of previous requests.',
            Marks: '1',
            Category: 'Web Fundamentals',
            'Question Type': 'true_false',
            Reference: '',
        },
        {
            Question: 'Fill in the blank: The command used to create a Laravel migration is ____.',
            'Option A': '',
            'Option B': '',
            'Option C': '',
            'Option D': '',
            'Correct Option(s)': 'php artisan make:migration',
            Explanation: '',
            Marks: '2',
            Category: 'Development > PHP > Laravel',
            'Question Type': 'fill_blank',
            Reference: 'Artisan CLI',
        },
        {
            Question: 'Explain dependency injection and give one practical benefit.',
            'Option A': '',
            'Option B': '',
            'Option C': '',
            'Option D': '',
            'Correct Option(s)': 'Answers should explain supplying dependencies externally and improved testability.',
            Explanation: 'Review descriptive answers manually.',
            Marks: '5',
            Category: 'Software Engineering > Architecture',
            'Question Type': 'long_answer',
            Reference: '',
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
        correct_options: ['correct_options', 'correct_option', 'correct_option_s', 'correct_answers', 'correct_answer', 'answer', 'answers'],
        explanation: ['explanation'],
        reference: ['reference'],
        status: ['status'],
    };

    const COLUMN_WIDTHS = {
        Question: 48,
        'Option A': 18,
        'Option B': 18,
        'Option C': 18,
        'Option D': 18,
        'Correct Option(s)': 22,
        Explanation: 36,
        Marks: 10,
        Category: 32,
        'Question Type': 16,
        Reference: 20,
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
        let fallback = '';
        for (const alias of aliases) {
            if (!Object.prototype.hasOwnProperty.call(source, alias)) continue;
            const value = source[alias];
            if (String(value ?? '').trim() !== '') return value;
            fallback = value;
        }
        return fallback;
    };

    const parseMarks = (value) => String(value || '')
        .split(/[,;|]/)
        .map((item) => Number(item.trim()))
        .filter((item) => Number.isInteger(item) && item >= 1 && item <= 10);

    const normalizeRow = (raw, rowNumber) => {
        const source = {};
        Object.entries(raw || {}).forEach(([key, value]) => {
            source[normalizeHeader(key)] = value == null ? '' : String(value).trim();
        });

        const row = { _row: rowNumber, _removed: false, _errors: [], _serverErrors: [] };
        Object.entries(HEADER_ALIASES).forEach(([key, aliases]) => {
            row[key] = String(findValue(source, aliases) ?? '').trim();
        });

        row.type = row.type.toLowerCase().replace(/\s+/g, '_');
        row.difficulty = (row.difficulty || DEFAULT_DIFFICULTY).toLowerCase().replace(/\s+/g, '_');
        if (!['easy', 'medium', 'hard', 'very_hard'].includes(row.difficulty)) {
            row.difficulty = DEFAULT_DIFFICULTY;
        }

        const marksValues = parseMarks(row.marks);
        row.marks_type = (row.marks_type || (marksValues.length > 1 ? 'multiple' : DEFAULT_MARKS_TYPE))
            .toLowerCase()
            .replace(/\s+/g, '_');
        if (!['single', 'multiple'].includes(row.marks_type)) {
            row.marks_type = marksValues.length > 1 ? 'multiple' : DEFAULT_MARKS_TYPE;
        }
        if (!row.marks) row.marks = DEFAULT_MARKS;

        row.status = (row.status || DEFAULT_STATUS).toLowerCase();
        if (!['active', 'inactive', 'suspended'].includes(row.status)) {
            row.status = DEFAULT_STATUS;
        }

        return row;
    };

    const validateRow = (row, duplicateKeys = null) => {
        const errors = [];
        if (!row.question) errors.push('Question is required');
        if (!TYPES.includes(row.type)) {
            errors.push('Invalid question type (use mcq, true_false, fill_blank, short_answer, or long_answer)');
        }
        if (!row.category) errors.push('Category is required');
        if (row.category && row.category.split('>').some((part) => !part.trim())) {
            errors.push('Invalid category path — use Parent > Child');
        }

        const marks = parseMarks(row.marks);
        if (!marks.length) errors.push('Marks must be whole numbers from 1 to 10');
        if (row.marks_type === 'single' && marks.length !== 1) {
            errors.push('Single marks requires one value between 1 and 10');
        }

        const answer = String(row.correct_options || '').trim();
        if (row.type === 'mcq') {
            const options = ['A', 'B', 'C', 'D', 'E', 'F']
                .filter((letter) => row[`option_${letter.toLowerCase()}`]);
            if (options.length < 2) errors.push('MCQ needs at least two options (A–D)');

            const labels = answer
                .toUpperCase()
                .split(/[\s,;|]+/)
                .filter(Boolean);
            if (!labels.length) errors.push('Correct Option(s) is required');
            if (labels.some((label) => !options.includes(label))) {
                errors.push('Correct Option(s) must match filled option labels (e.g. A or A,C)');
            }
        } else if (!answer) {
            errors.push('Correct Option(s) is required');
        }

        if (row.type === 'true_false' && answer && !['true', 'false'].includes(answer.toLowerCase())) {
            errors.push('True/False Correct Option(s) must be True or False');
        }

        if (duplicateKeys) {
            const key = row.question.trim().toLowerCase();
            if (key && duplicateKeys.has(key) && duplicateKeys.get(key) !== row._row) {
                errors.push('Duplicate question text in this file');
            }
        }

        const serverErrors = Array.isArray(row._serverErrors) ? row._serverErrors : [];
        row._errors = [...errors, ...serverErrors];
        return row._errors.length === 0;
    };

    const buildDuplicateMap = (rows) => {
        const map = new Map();
        rows.filter((row) => !row._removed).forEach((row) => {
            const key = String(row.question || '').trim().toLowerCase();
            if (!key) return;
            if (!map.has(key)) map.set(key, row._row);
        });
        return map;
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

        const setStep = (step) => {
            modal.querySelectorAll('[data-step-indicator]').forEach((item) => {
                const value = Number(item.dataset.stepIndicator);
                item.classList.toggle('is-active', value === step);
                item.classList.toggle('is-complete', value < step);
            });
        };

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
            const duplicates = buildDuplicateMap(rows);
            activeRows().forEach((row) => validateRow(row, duplicates));
            const valid = activeRows().filter((row) => row._errors.length === 0).length;
            const invalid = activeRows().length - valid;
            document.getElementById('qimport-total').textContent = activeRows().length;
            document.getElementById('qimport-valid').textContent = valid;
            document.getElementById('qimport-invalid').textContent = invalid;
            importBtn.disabled = importing || valid === 0;
            if (activeRows().length) setStep(importing ? 3 : 2);
        };

        const filteredRows = () => {
            const query = searchInput.value.trim().toLowerCase();
            const filter = filterSelect.value;
            return activeRows().filter((row) => {
                const matchesQuery = !query || [
                    row.question, row.category, row.type, row.correct_options, row.reference,
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
                return `<tr class="${row._errors.length ? 'is-invalid' : 'is-valid'}">
                    <td class="qimport-col-row">${row._row}</td>
                    <td>${status}</td>
                    <td>${input(index, 'question', row.question, true)}</td>
                    <td>${input(index, 'option_a', row.option_a)}</td>
                    <td>${input(index, 'option_b', row.option_b)}</td>
                    <td>${input(index, 'option_c', row.option_c)}</td>
                    <td>${input(index, 'option_d', row.option_d)}</td>
                    <td>${input(index, 'correct_options', row.correct_options)}</td>
                    <td>${input(index, 'explanation', row.explanation, true)}</td>
                    <td>${input(index, 'marks', row.marks)}</td>
                    <td>${input(index, 'category', row.category, true)}</td>
                    <td>${select(index, 'type', row.type, TYPES)}</td>
                    <td>${input(index, 'reference', row.reference)}</td>
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
            setStep(1);
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
            setStep(2);

            try {
                await new Promise((resolve) => setTimeout(resolve, 30));
                const data = await file.arrayBuffer();
                setProgress(25, 'Parsing worksheet');
                const workbook = global.XLSX.read(data, { type: 'array', cellDates: false });
                const sheetName = workbook.SheetNames.find((name) => normalizeHeader(name) === 'questions')
                    || workbook.SheetNames[0];
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
                    toggle.textContent = 'Show guide';
                }
                setProcessing(false);
                setProgress(100, 'File parsed — review validation results');
                render();
            } catch (error) {
                rows = [];
                review.hidden = true;
                setStep(1);
                fail(error.message || 'Unable to parse this file.');
            }
        };

        const toPayloadRow = (row) => {
            const payload = { _row: row._row };
            [...EDITABLE_KEYS, 'option_e', 'option_f', 'difficulty', 'marks_type', 'status'].forEach((key) => {
                payload[key] = row[key] || '';
            });
            return payload;
        };

        const importRows = async () => {
            const currentRows = activeRows();
            const duplicates = buildDuplicateMap(rows);
            currentRows.forEach((row) => validateRow(row, duplicates));
            const validRows = currentRows.filter((row) => row._errors.length === 0);
            const invalidRows = currentRows.filter((row) => row._errors.length > 0);
            if (!validRows.length || !sourceFile || importing) return;

            importing = true;
            refreshSummary();
            importBtn.textContent = 'Importing…';
            setStep(3);
            setProcessing(true, 'Importing questions…', 'The import continues in small batches. Keep this window open.');
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
                startData.append('total_rows', String(currentRows.length));
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
                setStep(2);
                fail(error.message || 'Unable to start the import.');
                refreshSummary();
                return;
            }

            for (let i = 0; i < chunks.length; i++) {
                setProgress(((i + 0.35) / chunks.length) * 100, `Processing batch ${i + 1} of ${chunks.length}`);
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
                            rows: chunks[i].map(toPayloadRow),
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
            setProcessing(false);
            const failedCount = invalidRows.length + failures.length;

            const applyServerFailures = () => {
                const byRow = new Map();
                failures.forEach((item) => {
                    const rowNumber = Number(item.row || 0);
                    if (!rowNumber) return;
                    const messages = (item.errors || [])
                        .map((message) => String(message || '').trim())
                        .filter(Boolean);
                    if (!messages.length) return;
                    byRow.set(rowNumber, [...(byRow.get(rowNumber) || []), ...messages]);
                });

                rows.forEach((row) => {
                    row._serverErrors = byRow.has(row._row)
                        ? [...new Set(byRow.get(row._row))]
                        : [];
                });
            };

            if (imported > 0) {
                global.EmsToast?.success?.(
                    failedCount > 0
                        ? `${imported} questions imported (${failedCount} failed).`
                        : `${imported} questions imported.`,
                );
                document.dispatchEvent(new CustomEvent('questions:imported', {
                    detail: { imported, importQuestionId },
                }));
                reset();
                close();
                return;
            }

            applyServerFailures();
            if (filterSelect.value === 'all' && failures.length) {
                filterSelect.value = 'invalid';
            }
            page = 1;
            setProgress(100, `Completed: 0 imported, ${failedCount} failed`);
            setStep(2);

            const sampleError = failures[0]?.errors?.[0] || 'Validation failed on the server.';
            results.hidden = false;
            results.classList.add('has-errors');
            results.classList.remove('is-success');
            results.innerHTML = `<div class="qimport-results__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/></svg>
            </div>
            <div>
                <strong>No questions were imported.</strong>
                <p>${escapeHtml(sampleError)}${failedCount > 1 ? ` (${failedCount} rows failed — see Validation column.)` : ''}</p>
            </div>`;
            global.EmsToast?.error?.(sampleError);
            render();
        };

        const downloadCsvSample = () => {
            if (!global.XLSX) return fail('Spreadsheet library is unavailable.');
            const worksheet = global.XLSX.utils.json_to_sheet(SAMPLE_ROWS, { header: SAMPLE_HEADERS });
            const csv = global.XLSX.utils.sheet_to_csv(worksheet);
            const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = 'question-import-sample.csv';
            anchor.click();
            URL.revokeObjectURL(url);
        };

        const downloadXlsxSample = async () => {
            if (!global.ExcelJS) return fail('Excel template library is unavailable. Refresh and try again.');

            const workbook = new global.ExcelJS.Workbook();
            workbook.creator = 'Exam Management System';
            workbook.created = new Date();

            const sheet = workbook.addWorksheet('Questions', {
                views: [{ state: 'frozen', ySplit: 1, showGridLines: true }],
                properties: { defaultRowHeight: 22 },
            });

            sheet.columns = SAMPLE_HEADERS.map((header) => ({
                header,
                key: header,
                width: COLUMN_WIDTHS[header] || 16,
                style: {
                    alignment: { vertical: 'middle', wrapText: true },
                    font: { name: 'Calibri', size: 11, color: { argb: 'FF0F172A' } },
                },
            }));

            const headerRow = sheet.getRow(1);
            headerRow.height = 28;
            headerRow.eachCell((cell) => {
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FF1E3A5F' },
                };
                cell.font = {
                    name: 'Calibri',
                    size: 11,
                    bold: true,
                    color: { argb: 'FFFFFFFF' },
                };
                cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
                cell.border = {
                    top: { style: 'thin', color: { argb: 'FF16324F' } },
                    left: { style: 'thin', color: { argb: 'FF16324F' } },
                    bottom: { style: 'thin', color: { argb: 'FF16324F' } },
                    right: { style: 'thin', color: { argb: 'FF16324F' } },
                };
            });

            SAMPLE_ROWS.forEach((sample, index) => {
                const row = sheet.addRow(SAMPLE_HEADERS.map((header) => sample[header] ?? ''));
                row.height = 36;
                row.eachCell((cell, colNumber) => {
                    const isAlt = index % 2 === 1;
                    cell.fill = {
                        type: 'pattern',
                        pattern: 'solid',
                        fgColor: { argb: isAlt ? 'FFF1F5F9' : 'FFFFFFFF' },
                    };
                    cell.border = {
                        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    };
                    cell.alignment = {
                        vertical: 'middle',
                        horizontal: SAMPLE_HEADERS[colNumber - 1] === 'Marks' ? 'center' : 'left',
                        wrapText: true,
                    };
                });
            });

            sheet.autoFilter = {
                from: { row: 1, column: 1 },
                to: { row: 1, column: SAMPLE_HEADERS.length },
            };

            const typeCol = SAMPLE_HEADERS.indexOf('Question Type') + 1;
            sheet.dataValidations.add(`${colLetter(typeCol)}2:${colLetter(typeCol)}1001`, {
                type: 'list',
                allowBlank: true,
                formulae: [`"${TYPES.join(',')}"`],
                showErrorMessage: true,
                errorTitle: 'Invalid question type',
                error: 'Choose one of: mcq, true_false, fill_blank, short_answer, long_answer',
            });

            const marksCol = SAMPLE_HEADERS.indexOf('Marks') + 1;
            sheet.dataValidations.add(`${colLetter(marksCol)}2:${colLetter(marksCol)}1001`, {
                type: 'whole',
                operator: 'between',
                formulae: [1, 10],
                allowBlank: true,
                showErrorMessage: true,
                errorTitle: 'Invalid marks',
                error: 'Marks must be a whole number from 1 to 10.',
            });

            const guide = workbook.addWorksheet('Instructions', {
                properties: { defaultRowHeight: 20 },
            });
            guide.getColumn(1).width = 28;
            guide.getColumn(2).width = 72;
            guide.addRow(['Question Import Guide', '']);
            guide.mergeCells('A1:B1');
            guide.getRow(1).font = { bold: true, size: 14, color: { argb: 'FF1E3A5F' } };
            guide.getRow(1).height = 28;

            const guideRows = [
                ['Columns', 'Only the Questions sheet columns are imported. Other fields use Create Question defaults.'],
                ['Defaults applied', 'Difficulty = medium · Status = active · Marks type = single · Marks = 1 when blank'],
                ['Correct Option(s)', 'MCQ: A or A,B,D · True/False: True or False · Other types: answer text'],
                ['Category', 'Use Parent > Child. Missing categories are created automatically.'],
                ['Question Type', 'Use the dropdown: mcq, true_false, fill_blank, short_answer, long_answer'],
                ['Duplicates', 'Repeated question text in the file or question bank will be skipped.'],
            ];
            guideRows.forEach((item) => {
                const row = guide.addRow(item);
                row.getCell(1).font = { bold: true, color: { argb: 'FF1E3A5F' } };
                row.getCell(2).alignment = { wrapText: true, vertical: 'middle' };
                row.height = 30;
            });

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = 'question-import-sample.xlsx';
            anchor.click();
            URL.revokeObjectURL(url);
        };

        const colLetter = (index) => {
            let n = index;
            let letter = '';
            while (n > 0) {
                const rem = (n - 1) % 26;
                letter = String.fromCharCode(65 + rem) + letter;
                n = Math.floor((n - 1) / 26);
            }
            return letter;
        };

        const downloadSample = (format) => {
            if (format === 'csv') return downloadCsvSample();
            return downloadXlsxSample();
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
            rulesToggle.textContent = collapsed ? 'Show guide' : 'Hide guide';
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
            const row = rows[Number(field.dataset.rowIndex)];
            if (!row) return;
            row[field.dataset.key] = field.value.trim();
            row._serverErrors = [];
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
