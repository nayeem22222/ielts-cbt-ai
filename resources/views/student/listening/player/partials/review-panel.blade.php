<div id="listening-review-shell" class="listening-review-shell hidden" role="dialog" aria-modal="true" aria-labelledby="listening-review-title">
    <div id="listening-review-backdrop" class="listening-review-backdrop" aria-hidden="true"></div>

    <aside id="listening-review-panel" class="listening-review-panel">
        <div class="listening-review-panel-inner">
            <div class="listening-review-panel-head">
                <h2 id="listening-review-title" class="listening-review-title">Review Answers</h2>
                <button type="button" id="listening-review-close" class="listening-header-btn listening-header-btn-outline">Close</button>
            </div>

            <div class="listening-review-panel-body">
                <div class="listening-review-summary-grid">
                    <div class="listening-review-summary-card">
                        <span class="listening-review-summary-label">Total</span>
                        <p id="listening-review-total" class="listening-review-summary-value">0</p>
                    </div>
                    <div class="listening-review-summary-card is-answered">
                        <span class="listening-review-summary-label">Answered</span>
                        <p id="listening-review-answered" class="listening-review-summary-value">0</p>
                    </div>
                    <div class="listening-review-summary-card is-unanswered">
                        <span class="listening-review-summary-label">Unanswered</span>
                        <p id="listening-review-unanswered" class="listening-review-summary-value">0</p>
                    </div>
                    <div class="listening-review-summary-card is-flagged">
                        <span class="listening-review-summary-label">Flagged</span>
                        <p id="listening-review-flagged" class="listening-review-summary-value">0</p>
                    </div>
                    <div class="listening-review-summary-card listening-review-summary-card-wide">
                        <span class="listening-review-summary-label">Not visited</span>
                        <p id="listening-review-not-visited" class="listening-review-summary-value">0</p>
                    </div>
                </div>

                <div id="listening-review-parts"></div>
            </div>

            <div class="listening-review-actions">
                <button type="button" id="listening-review-unanswered-btn" class="listening-review-action-btn">Review unanswered</button>
                <button type="button" id="listening-review-flagged-btn" class="listening-review-action-btn">Review flagged</button>
                <button type="button" id="listening-review-continue-btn" class="listening-review-action-btn listening-review-action-btn-primary">Continue test</button>
            </div>
        </div>
    </aside>
</div>
