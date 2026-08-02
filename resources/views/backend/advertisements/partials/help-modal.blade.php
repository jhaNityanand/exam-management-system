<div id="ads-help-modal" class="ems-dialog hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ads-help-title">
    <div class="ems-dialog__backdrop" data-ads-modal-close="help"></div>
    <div class="ems-dialog__panel ems-dialog__panel--lg" role="document">
        <header class="ems-dialog__header">
            <div class="min-w-0">
                <h3 id="ads-help-title" class="ems-dialog__title">Advertisement help &amp; documentation</h3>
                <p class="ems-dialog__subtitle">Guidelines for configuring ads correctly across placements and devices.</p>
            </div>
            <button type="button" class="ems-dialog__close" data-ads-modal-close="help" aria-label="Close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>
        <div class="ems-dialog__body ads-help-body space-y-6">
            <section>
                <h4 class="ads-help-title">Advertisement types</h4>
                <ul class="ads-help-list">
                    <li><strong>Banner Image</strong> — Upload an image creative with optional click URL. Best for branded promotions.</li>
                    <li><strong>Iframe URL</strong> — Embed a remote creative or partner widget by URL with fixed or responsive dimensions.</li>
                    <li><strong>HTML Code</strong> — Custom HTML/CSS/JS snippets for lightweight self-hosted ads.</li>
                    <li><strong>Google Ad</strong> — AdSense (or similar) unit snippets managed as Google Ad configurations and placed separately.</li>
                </ul>
            </section>

            <section>
                <h4 class="ads-help-title">Recommended banner sizes</h4>
                <div class="ads-help-sizes">
                    @foreach($bannerSizes as $size)
                        <div class="ads-help-size">
                            <strong>{{ $size['label'] }}</strong>
                            <span>{{ $size['width'] }} × {{ $size['height'] }}</span>
                            <p>{{ $size['note'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section>
                <h4 class="ads-help-title">Recommended placement positions</h4>
                <ul class="ads-help-list">
                    <li><strong>Above / below title</strong> — High visibility without interrupting reading flow too early.</li>
                    <li><strong>Between sections</strong> — Natural break points in long articles; multiple ads allowed.</li>
                    <li><strong>Sidebars</strong> — Persistent visibility on desktop detail pages; stack multiple units carefully.</li>
                    <li><strong>Below every list item</strong> — Use sparingly on listing pages to avoid clutter.</li>
                    <li><strong>Above footer</strong> — Safe full-width strip on most layouts.</li>
                </ul>
            </section>

            <section>
                <h4 class="ads-help-title">Google Ads recommendations</h4>
                <ul class="ads-help-list">
                    <li>Load the AdSense/bootstrap script once via Custom Code → Header (or your existing integrations).</li>
                    <li>Create one configuration per ad unit (slot) and place the same unit only where policy allows.</li>
                    <li>Keep units inactive until publisher approval / policy checks are complete.</li>
                    <li>Prefer responsive formats for mixed desktop/mobile layouts.</li>
                </ul>
            </section>

            <section>
                <h4 class="ads-help-title">Custom advertisement recommendations</h4>
                <ul class="ads-help-list">
                    <li>Match banner size to the target slot (leaderboard for headers, rectangles for sidebars).</li>
                    <li>Always provide a clear target URL for banner ads.</li>
                    <li>Scope CSS classes uniquely for HTML ads to avoid theme conflicts.</li>
                    <li>Use iframes only for trusted HTTPS partners.</li>
                </ul>
            </section>

            <section>
                <h4 class="ads-help-title">Responsive behavior &amp; best practices</h4>
                <ul class="ads-help-list">
                    <li>Enable <em>Responsive</em> on iframe ads so they adapt on small screens.</li>
                    <li>Use mobile banner sizes for listing/mobile-heavy placements.</li>
                    <li>Disable placements temporarily instead of deleting them when running campaigns.</li>
                    <li>Avoid stacking too many ads in the same viewport — prioritize UX and policy compliance.</li>
                    <li>Frontend rendering of placements and global code is planned for a later phase; configure freely now.</li>
                </ul>
            </section>
        </div>
        <footer class="ems-dialog__footer">
            <button type="button" class="panel-button-primary text-sm" data-ads-modal-close="help">Got it</button>
        </footer>
    </div>
</div>
