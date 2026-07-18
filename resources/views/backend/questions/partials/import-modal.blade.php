<div id="question-import-modal" class="qimport-modal" hidden>
    <div class="qimport-modal__backdrop" data-import-close></div>
    <section class="qimport-dialog" role="dialog" aria-modal="true" aria-labelledby="question-import-title">
        <header class="qimport-dialog__header">
            <div>
                <p class="qimport-eyebrow">Bulk question tools</p>
                <h2 id="question-import-title">Import Questions</h2>
                <p>Upload, validate, review, and import an Excel or CSV question bank.</p>
            </div>
            <button type="button" class="qimport-icon-btn" data-import-close aria-label="Close import dialog">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </header>

        <div class="qimport-tabs" role="tablist" aria-label="Question import sources">
            <button type="button" class="qimport-tab is-active" id="qimport-tab-excel" role="tab"
                    aria-selected="true" aria-controls="qimport-panel-excel" data-import-tab="excel">
                Excel Import
            </button>
            <button type="button" class="qimport-tab" id="qimport-tab-other" role="tab"
                    aria-selected="false" aria-controls="qimport-panel-other" data-import-tab="other">
                Other Sources
            </button>
        </div>

        <div class="qimport-dialog__body">
            <div id="qimport-panel-excel" role="tabpanel" aria-labelledby="qimport-tab-excel">
                <div class="qimport-excel">
                    <section class="qimport-guide" aria-label="Import instructions">
                        <div class="qimport-guide__top">
                            <div class="qimport-section-heading">
                                <span class="qimport-step">1</span>
                                <div>
                                    <h3>Prepare your file</h3>
                                    <p>Download a sample, then upload your spreadsheet for review.</p>
                                </div>
                            </div>
                            <div class="qimport-template-actions">
                                <button type="button" class="qimport-secondary-btn" data-sample-format="xlsx">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12-4-4m4 4 4-4M4 20h16"/>
                                    </svg>
                                    Excel sample
                                </button>
                                <button type="button" class="qimport-secondary-btn" data-sample-format="csv">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12-4-4m4 4 4-4M4 20h16"/>
                                    </svg>
                                    CSV sample
                                </button>
                                <button type="button" class="qimport-link-btn" id="qimport-toggle-rules" aria-expanded="true" aria-controls="qimport-rules">
                                    Hide instructions
                                </button>
                            </div>
                        </div>

                        <div id="qimport-rules" class="qimport-rule-grid">
                            <div class="qimport-rule-card">
                                <strong>Formats &amp; limits</strong>
                                <p>.xlsx / .csv · max 15 MB · up to 10,000 rows</p>
                            </div>
                            <div class="qimport-rule-card">
                                <strong>Required columns</strong>
                                <p>Question, Type, Category, Difficulty, Marks Type, Marks</p>
                            </div>
                            <div class="qimport-rule-card">
                                <strong>MCQ options</strong>
                                <p>Option A–F · at least two options · answers as <code>A</code> or <code>A,B</code></p>
                            </div>
                            <div class="qimport-rule-card">
                                <strong>Categories</strong>
                                <p>Use <code>Parent &gt; Child</code>. Missing paths are created automatically.</p>
                            </div>
                            <div class="qimport-rule-card">
                                <strong>Types</strong>
                                <p>mcq, true_false, fill_blank, short_answer, long_answer</p>
                            </div>
                            <div class="qimport-rule-card">
                                <strong>Difficulty &amp; marks</strong>
                                <p>easy / medium / hard / very_hard · marks 1–10</p>
                            </div>
                        </div>
                    </section>

                    <section class="qimport-workspace">
                        <div class="qimport-section-heading">
                            <span class="qimport-step">2</span>
                            <div>
                                <h3>Upload and review</h3>
                                <p>Nothing is saved until you select Import questions.</p>
                            </div>
                        </div>

                        <input type="file" id="question-import-file" accept=".xlsx,.csv" hidden>
                        <button type="button" id="question-import-dropzone" class="qimport-dropzone">
                            <span class="qimport-dropzone__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0L7 9m5-5 5 5M4 15v3a2 2 0 002 2h12a2 2 0 002-2v-3"/>
                                </svg>
                            </span>
                            <span><strong>Choose a spreadsheet</strong> or drag it here</span>
                            <small>XLSX or CSV · up to 15 MB</small>
                        </button>

                        <div id="qimport-file-card" class="qimport-file-card" hidden>
                            <div>
                                <strong id="qimport-file-name"></strong>
                                <span id="qimport-file-meta"></span>
                            </div>
                            <button type="button" id="qimport-clear-file" class="qimport-link-btn">Replace</button>
                        </div>

                        <div id="qimport-processing" class="qimport-processing" hidden aria-live="polite">
                            <span class="qimport-spinner"></span>
                            <div>
                                <strong id="qimport-processing-title">Reading spreadsheet…</strong>
                                <span id="qimport-processing-detail">Large files may take a moment.</span>
                            </div>
                        </div>

                        <div id="qimport-review" hidden>
                            <div class="qimport-summary">
                                <div><span>Total rows</span><strong id="qimport-total">0</strong></div>
                                <div class="is-valid"><span>Ready</span><strong id="qimport-valid">0</strong></div>
                                <div class="is-error"><span>Needs attention</span><strong id="qimport-invalid">0</strong></div>
                            </div>

                            <div class="qimport-review-toolbar">
                                <input id="qimport-search" type="search" class="panel-input" placeholder="Search preview…">
                                <select id="qimport-filter" class="panel-input">
                                    <option value="all">All rows</option>
                                    <option value="valid">Ready only</option>
                                    <option value="invalid">Errors only</option>
                                </select>
                                <span id="qimport-page-status"></span>
                            </div>

                            <div class="qimport-table-wrap">
                                <table class="qimport-table">
                                    <thead>
                                        <tr>
                                            <th class="qimport-col-row">#</th>
                                            <th class="qimport-col-status">Validation</th>
                                            <th class="qimport-col-question">Question *</th>
                                            <th class="qimport-col-option">Option A</th>
                                            <th class="qimport-col-option">Option B</th>
                                            <th class="qimport-col-option">Option C</th>
                                            <th class="qimport-col-option">Option D</th>
                                            <th class="qimport-col-answer">Correct option</th>
                                            <th class="qimport-col-answers">Correct options</th>
                                            <th class="qimport-col-explain">Explanation</th>
                                            <th class="qimport-col-type">Type *</th>
                                            <th class="qimport-col-category">Category *</th>
                                            <th class="qimport-col-diff">Difficulty *</th>
                                            <th class="qimport-col-marks-type">Marks type *</th>
                                            <th class="qimport-col-marks">Marks *</th>
                                            <th class="qimport-col-action">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="qimport-preview-body"></tbody>
                                </table>
                            </div>

                            <div class="qimport-pagination">
                                <button type="button" id="qimport-prev" class="qimport-secondary-btn">Previous</button>
                                <button type="button" id="qimport-next" class="qimport-secondary-btn">Next</button>
                            </div>
                        </div>

                        <div id="qimport-results" class="qimport-results" hidden aria-live="polite"></div>
                    </section>
                </div>
            </div>

            <div id="qimport-panel-other" class="qimport-coming-soon" role="tabpanel"
                 aria-labelledby="qimport-tab-other" hidden>
                <div class="qimport-coming-soon__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3>Coming Soon!</h3>
                <p>
                    Additional import sources are still in progress. For now, use the Excel Import tab
                    to upload validated XLSX or CSV question banks.
                </p>
                <button type="button" class="qimport-primary-btn" data-import-tab="excel">
                    Back to Excel Import
                </button>
            </div>
        </div>

        <footer class="qimport-dialog__footer">
            <div class="qimport-progress-area">
                <div class="qimport-progress-track"><span id="qimport-progress-bar"></span></div>
                <span id="qimport-progress-text">No file selected</span>
            </div>
            <div class="qimport-footer-actions">
                <button type="button" class="qimport-secondary-btn" data-import-close>Cancel</button>
                <button type="button" id="qimport-import-btn" class="qimport-primary-btn" disabled>
                    Import questions
                </button>
            </div>
        </footer>
    </section>
</div>
