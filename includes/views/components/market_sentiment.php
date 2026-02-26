<?php
/**
 * Standalone Market Sentiment Component (Bottom Sheet)
 */
?>
<div id="market-sentiment-container" class="d-none" data-currency-id="<?= htmlspecialchars($target_id) ?>" data-currency-name="<?= htmlspecialchars($target_name) ?>">
    <div class="sentiment-bottom-sheet">
        <div class="sentiment-content-wrapper">
            <!-- Mode 1: Prediction (Initial) -->
            <div id="sentiment-prediction-mode" class="sentiment-mode d-none">
                <div class="sentiment-header">
                    <div class="d-flex align-center gap-05">
                        <div class="sentiment-title-icon">
                            <i data-lucide="trending-up-down" class="icon-size-4"></i>
                        </div>
                        <h3 class="sentiment-title">پیش‌بینی بازار</h3>
                   </div>
                   
                   <button class="sentiment-close-btn" id="sentiment-close-btn">
                        <i data-lucide="x" class="icon-size-4"></i>
                    </button>

                </div>

                <p class="sentiment-question"><strong class="text-primary"><?= htmlspecialchars($target_name) ?></strong> بر سر دوراهی؛ صعود یا نزول؟ پیش‌بینی شما از قیمت امروز چیست؟</p>

                <div class="sentiment-actions">
                    <button class="sentiment-btn bearish-btn" data-vote="bearish">
                         <span class="font-bold">نزولی</span>
                         <i data-lucide="trending-down" class="icon-size-5"></i>
                    </button>
                    <button class="sentiment-btn bullish-btn" data-vote="bullish">
                         <span class="font-bold">صعودی</span>
                         <i data-lucide="trending-up" class="icon-size-5"></i>
                    </button>
                </div>
            </div>

            <!-- Mode 2: Results -->
            <div id="sentiment-result-mode" class="sentiment-mode d-none">
                <div class="sentiment-header">
                    <div class="sentiment-title-icon">
                        <i data-lucide="trending-up" class="text-success icon-size-4"></i>
                        <i data-lucide="trending-down" class="text-error icon-size-4"></i>
                   </div>
                   <h3 class="sentiment-title">نتیجه پیش‌بینی</h3>
                </div>

                <p id="sentiment-result-text" class="sentiment-result-text">
                    <!-- Dynamic text here -->
                </p>

                <div class="sentiment-progress-container">
                    <div class="sentiment-progress-labels">
                        <span class="text-error font-bold">نزولی</span>
                        <span class="text-success font-bold">صعودی</span>
                    </div>
                    <div class="sentiment-progress-bar">
                        <div id="sentiment-bearish-bar" class="sentiment-bar-segment bearish"></div>
                        <div id="sentiment-bullish-bar" class="sentiment-bar-segment bullish"></div>
                    </div>
                    <div class="sentiment-percentage-labels">
                        <span id="sentiment-bearish-percent-label" class="text-error font-bold">۰%</span>
                        <span id="sentiment-bullish-percent-label" class="text-success font-bold">۰%</span>
                    </div>
                </div>

                <div class="sentiment-footer">
                    <span class="text-gray font-size-0-8">تعداد رأی دهندگان</span>
                    <strong id="sentiment-total-votes" class="text-title">۰ کاربر</strong>
                </div>
            </div>
        </div>
    </div>
</div>
