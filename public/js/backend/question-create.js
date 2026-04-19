document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('type');
    const sections = {
        'mcq': document.getElementById('section-mcq'),
        'true_false': document.getElementById('section-true-false'),
        'short_answer': document.getElementById('section-short-answer')
    };

    const allowsMultiple = document.getElementById('allows_multiple');
    const optionsContainer = document.getElementById('options-container');
    const btnAddOption = document.getElementById('btn-add-option');
    const optionTemplate = document.getElementById('option-template');
    const form = document.getElementById('question-form');

    let optionCount = 0;
    const optionEditors = {}; // To store CKEditor instances

    // ckeditor config
    const editorConfig = {
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
        placeholder: 'Type your content here...'
    };

    // Initialize main editors
    let bodyEditor, explanationEditor, saAnswerEditor;
    
    ClassicEditor
        .create(document.querySelector('#editor-body'), editorConfig)
        .then(editor => { bodyEditor = editor; })
        .catch(error => { console.error(error); });

    if(document.querySelector('#editor-sa-answer')) {
        ClassicEditor
            .create(document.querySelector('#editor-sa-answer'), editorConfig)
            .then(editor => { saAnswerEditor = editor; })
            .catch(error => { console.error(error); });
    }

    ClassicEditor
        .create(document.querySelector('#editor-explanation'), editorConfig)
        .then(editor => { explanationEditor = editor; })
        .catch(error => { console.error(error); });

    // Handle Type Change
    const updateSections = () => {
        const val = typeSelect.value;
        Object.values(sections).forEach(s => s.classList.add('hidden'));
        if (sections[val]) {
            sections[val].classList.remove('hidden');
        }
    };

    typeSelect.addEventListener('change', updateSections);
    updateSections();

    // Handle initial options for MCQ
    const addOption = () => {
        const idx = optionCount++;
        const clone = optionTemplate.content.cloneNode(true);
        const item = clone.querySelector('.option-item');
        item.dataset.index = idx;
        
        // Setup correct answer input
        const input = item.querySelector('.correct-answer-input');
        input.name = allowsMultiple.checked ? `correct_answers[]` : `correct_answer`;
        input.type = allowsMultiple.checked ? 'checkbox' : 'radio';
        input.value = `option_${idx}`;
        
        // Insert into DOM before initializing editor
        optionsContainer.appendChild(clone);
        
        // Initialize CKEditor for this option
        const editorDiv = item.querySelector('.option-editor-container');
        ClassicEditor
            .create(editorDiv, editorConfig)
            .then(editor => {
                optionEditors[idx] = editor;
            })
            .catch(error => { console.error(error); });
    };

    // Add first two options by default
    addOption();
    addOption();

    btnAddOption.addEventListener('click', addOption);

    // Remove Option
    optionsContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remove-option');
        if (btn) {
            const item = btn.closest('.option-item');
            const idx = item.dataset.index;
            if (optionEditors[idx]) {
                optionEditors[idx].destroy();
                delete optionEditors[idx];
            }
            item.remove();
        }
    });

    // Handle allows multiple toggle (change radios to checkboxes and vice versa)
    allowsMultiple.addEventListener('change', (e) => {
        const inputs = optionsContainer.querySelectorAll('.correct-answer-input');
        inputs.forEach(input => {
            input.type = e.target.checked ? 'checkbox' : 'radio';
            input.name = e.target.checked ? `correct_answers[]` : `correct_answer`;
        });
    });

    // Form Submit handling (sync editors)
    form.addEventListener('submit', (e) => {
        e.preventDefault(); // Stop standard submit temporarily
        
        let isValid = true;
        
        // Hide all errors
        document.querySelectorAll('[id^="err-"]').forEach(el => el.classList.add('hidden'));

        // 1. Category validation
        const categoryId = document.getElementById('category_id').value;
        if (!categoryId) {
            document.getElementById('err-category_id').classList.remove('hidden');
            isValid = false;
        }

        // 2. Marks validation
        const marks = parseFloat(document.getElementById('marks').value);
        if (isNaN(marks) || marks <= 0) {
            document.getElementById('err-marks').classList.remove('hidden');
            isValid = false;
        }

        // 3. Question Body
        const qBodyHtml = bodyEditor ? bodyEditor.getData().trim() : '';
        const qBodyText = stripHtml(qBodyHtml).trim();
        if (qBodyText === '') {
            document.getElementById('err-body').classList.remove('hidden');
            isValid = false;
        }
        document.getElementById('body').value = qBodyHtml;
        
        // 4. Type specific validations
        const type = typeSelect.value;
        const items = optionsContainer.querySelectorAll('.option-item');
        
        if (type === 'mcq') {
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
                const errEl = document.getElementById('err-options');
                if (!optionsValid) {
                    errEl.textContent = 'All options must have content.';
                } else if (!hasValidOptionsCount) {
                    errEl.textContent = 'Provide at least 2 options.';
                } else {
                    errEl.textContent = 'Select at least one correct answer.';
                }
                errEl.classList.remove('hidden');
                isValid = false;
            }
        } else if (type === 'true_false') {
            const checked = document.querySelector('input[name="tf_answer"]:checked');
            if (!checked) {
                document.getElementById('err-tf').classList.remove('hidden');
                isValid = false;
            }
        } else if (type === 'short_answer') {
            const saHtml = saAnswerEditor ? saAnswerEditor.getData().trim() : '';
            const saText = stripHtml(saHtml).trim();
            if (saText === '') {
                document.getElementById('err-sa').classList.remove('hidden');
                isValid = false;
            }
            document.getElementById('sa_answer').value = saHtml;
        }

        if (!isValid) {
            return;
        }

        // --- All Validations Passed ---
        
        // Copy explanation editor data
        document.getElementById('explanation').value = explanationEditor ? explanationEditor.getData() : '';

        // Clean up previous hidden inputs for options
        document.querySelectorAll('.appended-option-input').forEach(el => el.remove());

        if (type === 'mcq') {
            // Loop through each option, extract CKEditor HTML, attach hidden inputs for options array
            Array.from(items).forEach((item, index) => {
                const idx = item.dataset.index;
                const htmlContent = optionEditors[idx] ? optionEditors[idx].getData() : '';
                
                const hiddenTextInput = document.createElement('input');
                hiddenTextInput.type = 'hidden';
                hiddenTextInput.name = `options[${index}][text]`;
                hiddenTextInput.value = htmlContent;
                hiddenTextInput.classList.add('appended-option-input');
                form.appendChild(hiddenTextInput);

                // Check correct answers and remap their values from "option_N" to the raw text
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
        } else if (type === 'short_answer') {
            const html = document.getElementById('sa_answer').value;
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'correct_answer';
            hidden.value = html;
            hidden.classList.add('appended-option-input');
            form.appendChild(hidden);
        }

        // Submitting
        form.submit();
    });

    function stripHtml(html) {
        let tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }
});
