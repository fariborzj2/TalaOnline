/**
 * Standalone Market Sentiment Component Script
 */

document.addEventListener('DOMContentLoaded', function() {
    const sentimentContainer = document.getElementById('market-sentiment-container');
    if (!sentimentContainer) return;

    const currencyId = sentimentContainer.dataset.currencyId;
    const currencyName = sentimentContainer.dataset.currencyName;
    const predictionMode = document.getElementById('sentiment-prediction-mode');
    const resultMode = document.getElementById('sentiment-result-mode');
    const closeBtn = document.getElementById('sentiment-close-btn');
    const csrfToken = window.__AUTH_STATE__?.csrfToken;

    let isTriggered = false;
    let hasVoted = false;

    const toPersianDigits = (num) => {
        if (num === null || num === undefined) return '';
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return num.toString().replace(/\d/g, x => persian[x]);
    };

    const showSentiment = async () => {
        if (isTriggered) return;
        isTriggered = true;

        try {
            const response = await fetch(`/api/sentiment.php?action=get_results&currency_id=${currencyId}`);
            const data = await response.json();

            if (data.success) {
                updateUI(data);
                sentimentContainer.classList.remove('d-none');
                sentimentContainer.classList.add('active');
                if (window.lucide) lucide.createIcons({ root: sentimentContainer });
            }
        } catch (err) {
            console.error('Failed to load sentiment data:', err);
        }
    };

    const updateUI = (data) => {
        const results = data.results;
        const userVote = data.user_vote;
        const todayFa = data.today_fa || 'امروز';

        if (userVote) {
            hasVoted = true;
            predictionMode.classList.add('d-none');
            resultMode.classList.remove('d-none');

            // Text logic
            const majorityType = results.bullish_percent >= results.bearish_percent ? 'خوش‌بین' : 'بدبین';
            const majorityPercent = Math.max(results.bullish_percent, results.bearish_percent);
            const resultText = `از جمع <strong class="text-primary">${toPersianDigits(results.total)}</strong> کاربر رأی‌دهنده، <strong class="text-success">${toPersianDigits(majorityPercent)} درصد</strong> نسبت به روند رشد/نزول <strong class="text-primary">${currencyName}</strong> در تاریخ <span class="text-gray">${toPersianDigits(todayFa)}</span> ${majorityType} هستند.`;

            document.getElementById('sentiment-result-text').innerHTML = resultText;
            document.getElementById('sentiment-total-votes').textContent = `${toPersianDigits(results.total)} کاربر`;

            // Bars
            const bullishBar = document.getElementById('sentiment-bullish-bar');
            const bearishBar = document.getElementById('sentiment-bearish-bar');

            bullishBar.style.width = `${results.bullish_percent}%`;
            bearishBar.style.width = `${results.bearish_percent}%`;

            bullishBar.querySelector('.sentiment-percent').textContent = `${toPersianDigits(results.bullish_percent)}%`;
            bearishBar.querySelector('.sentiment-percent').textContent = `${toPersianDigits(results.bearish_percent)}%`;

            // Highlight user's selection
            bullishBar.classList.remove('user-selection');
            bearishBar.classList.remove('user-selection');
            if (userVote === 'bullish') bullishBar.classList.add('user-selection');
            if (userVote === 'bearish') bearishBar.classList.add('user-selection');

        } else {
            predictionMode.classList.remove('d-none');
            resultMode.classList.add('d-none');
        }
    };

    const handleVote = async (vote) => {
        try {
            const response = await fetch(`/api/sentiment.php?action=vote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ currency_id: currencyId, vote: vote })
            });
            const data = await response.json();
            if (data.success) {
                updateUI(data);
                // After vote, if it was prediction mode, it will switch to result mode automatically via updateUI
            } else {
                if (window.showAlert) window.showAlert(data.message, 'error');
                else alert(data.message);
            }
        } catch (err) {
            console.error('Vote failed:', err);
        }
    };

    // Triggers
    const triggerEvents = () => {
        // 1. Scroll trigger (e.g. after 300px)
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) showSentiment();
        }, { passive: true });

        // 2. Mouse move trigger
        document.addEventListener('mousemove', showSentiment, { once: true });

        // 3. Delay trigger (5s)
        setTimeout(showSentiment, 5000);
    };

    // Close handler
    if (closeBtn) {
        closeBtn.onclick = () => {
            sentimentContainer.classList.add('closing');
            setTimeout(() => {
                sentimentContainer.classList.add('d-none');
                sentimentContainer.classList.remove('active', 'closing');
            }, 400);
        };
    }

    // Button handlers
    sentimentContainer.querySelectorAll('.sentiment-btn').forEach(btn => {
        btn.onclick = () => {
            const vote = btn.dataset.vote;
            handleVote(vote);
        };
    });

    triggerEvents();
});
