{{-- Exam Attempts modals (shared by list + show) --}}
<div id="exam-attempts-modal" class="ea-modal" hidden aria-hidden="true">
    <div class="ea-modal__backdrop" data-ea-close="attempts"></div>
    <div class="ea-modal__panel ea-modal__panel--xl" role="dialog" aria-modal="true" aria-labelledby="ea-attempts-title">
        <header class="ea-modal__header">
            <div class="ea-modal__header-main min-w-0">
                <div class="ea-modal__icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87M12 11a4 4 0 100-8 4 4 0 000 8zm6 3a3 3 0 100-6 3 3 0 000 6z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="ea-modal__eyebrow">Exam Attempts</p>
                    <h2 id="ea-attempts-title" class="ea-modal__title truncate">Candidates</h2>
                    <p class="ea-modal__subtitle" id="ea-attempts-subtitle">Loading attempters…</p>
                </div>
            </div>
            <button type="button" class="ea-modal__close" data-ea-close="attempts" aria-label="Close">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>

        <div class="ea-toolbar">
            <div class="ea-search ea-toolbar__search">
                <svg class="ea-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                <input type="search" id="ea-search" class="ea-search__input" placeholder="Search by name, email, phone, or ID…" autocomplete="off">
            </div>

            <div class="ea-filters" role="group" aria-label="Filters">
                <label class="ea-field">
                    <span class="ea-field__label">Status</span>
                    <select id="ea-filter-status" class="ea-select" aria-label="Filter by status">
                        <option value="">All</option>
                        <option value="passed">Passed</option>
                        <option value="failed">Failed</option>
                        <option value="completed">Completed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="auto_submitted">Auto Submitted</option>
                        <option value="abandoned">Abandoned</option>
                    </select>
                </label>
                <label class="ea-field">
                    <span class="ea-field__label">Result</span>
                    <select id="ea-filter-result" class="ea-select" aria-label="Filter by result">
                        <option value="">All</option>
                        <option value="passed">Pass</option>
                        <option value="failed">Fail</option>
                    </select>
                </label>
                <label class="ea-field">
                    <span class="ea-field__label">Email</span>
                    <select id="ea-filter-verified" class="ea-select" aria-label="Filter by email verification">
                        <option value="">All</option>
                        <option value="1">Verified</option>
                        <option value="0">Unverified</option>
                    </select>
                </label>
                <label class="ea-field">
                    <span class="ea-field__label">Sort</span>
                    <select id="ea-sort" class="ea-select" aria-label="Sort">
                        <option value="last_attempt_at:desc">Latest attempt</option>
                        <option value="last_attempt_at:asc">Oldest attempt</option>
                        <option value="attempts_count:desc">Most attempts</option>
                        <option value="name:asc">Name A–Z</option>
                        <option value="name:desc">Name Z–A</option>
                    </select>
                </label>
                <label class="ea-field ea-field--narrow">
                    <span class="ea-field__label">Per page</span>
                    <select id="ea-per-page" class="ea-select" aria-label="Per page">
                        <option value="15" selected>15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </label>
                <div class="ea-field ea-field--action">
                    <span class="ea-field__label">&nbsp;</span>
                    <button type="button" id="ea-filters-reset" class="ea-reset-btn" title="Reset filters">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="ea-modal__body" id="ea-attempts-body">
            <div id="ea-attempts-skeleton" class="ea-skeleton-list" hidden></div>
            <div id="ea-attempts-empty" class="ea-empty" hidden>
                <div class="ea-empty__icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 4a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <p class="ea-empty__title">No candidates yet</p>
                <p class="ea-empty__text">No one has attempted this exam, or no results match your filters.</p>
            </div>
            <div class="ea-table-wrap" id="ea-attempts-table-wrap" hidden>
                <table class="ea-table">
                    <thead>
                        <tr>
                            <th class="ea-th ea-th--candidate">Candidate</th>
                            <th class="ea-th ea-th--num">Attempts</th>
                            <th class="ea-th ea-th--score">Score</th>
                            <th class="ea-th ea-th--status">Status</th>
                            <th class="ea-th ea-th--date">Last attempt</th>
                            <th class="ea-th ea-th--verify">Verification</th>
                            <th class="ea-th ea-th--actions"><span class="ea-sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="ea-attempts-list"></tbody>
                </table>
            </div>
        </div>

        <footer class="ea-modal__footer">
            <p class="ea-meta" id="ea-attempts-meta"></p>
            <div class="ea-pagination" id="ea-attempts-pagination"></div>
        </footer>
    </div>
</div>

<div id="exam-attempt-history-modal" class="ea-modal ea-modal--nested" hidden aria-hidden="true">
    <div class="ea-modal__backdrop" data-ea-close="history"></div>
    <div class="ea-modal__panel ea-modal__panel--lg" role="dialog" aria-modal="true" aria-labelledby="ea-history-title">
        <header class="ea-modal__header">
            <div class="ea-modal__header-main min-w-0">
                <div class="ea-modal__icon ea-modal__icon--amber" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="ea-modal__eyebrow">Attempt History</p>
                    <h2 id="ea-history-title" class="ea-modal__title truncate">Attempts</h2>
                    <p class="ea-modal__subtitle" id="ea-history-subtitle"></p>
                </div>
            </div>
            <button type="button" class="ea-modal__close" data-ea-close="history" aria-label="Close">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>
        <div class="ea-modal__body" id="ea-history-body">
            <div id="ea-history-skeleton" class="ea-skeleton-list" hidden></div>
            <div id="ea-history-empty" class="ea-empty" hidden>
                <p class="ea-empty__title">No attempts found</p>
            </div>
            <div id="ea-history-list" class="ea-timeline"></div>
        </div>
    </div>
</div>

<div id="exam-attempt-verification-modal" class="ea-modal ea-modal--nested" hidden aria-hidden="true">
    <div class="ea-modal__backdrop" data-ea-close="verification"></div>
    <div class="ea-modal__panel ea-modal__panel--md" role="dialog" aria-modal="true" aria-labelledby="ea-verify-title">
        <header class="ea-modal__header">
            <div class="ea-modal__header-main min-w-0">
                <div class="ea-modal__icon ea-modal__icon--emerald" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="ea-modal__eyebrow">Verification</p>
                    <h2 id="ea-verify-title" class="ea-modal__title truncate">Documents</h2>
                    <p class="ea-modal__subtitle" id="ea-verify-subtitle"></p>
                </div>
            </div>
            <button type="button" class="ea-modal__close" data-ea-close="verification" aria-label="Close">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>
        <div class="ea-modal__body" id="ea-verify-body">
            <div id="ea-verify-skeleton" class="ea-skeleton-list" hidden></div>
            <div id="ea-verify-flags" class="ea-verify-flags"></div>
            <div id="ea-verify-empty" class="ea-empty" hidden>
                <p class="ea-empty__title">No verification documents</p>
                <p class="ea-empty__text">No profile image or exam snapshots are available for this candidate.</p>
            </div>
            <div id="ea-verify-grid" class="ea-verify-grid"></div>
        </div>
    </div>
</div>
