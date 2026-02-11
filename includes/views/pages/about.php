<div class="section">
    <div class="bg-block pd-md border basis-250 grow-1 radius-16">
        <div class="pb-1 mb-1 border-bottom bg-block">
            <h2 class="font-size-6 font-black text-title">درباره ما</h2>
            <p class="text-gray font-size-2 mt-1">آشنایی با تیم و اهداف وب‌سایت طلا آنلاین</p>
        </div>
        <div class="about-content text-title font-bold leading-relaxed">
            <?= $content ?>
        </div>
    </div>
</div>

<style>
    .about-content {
        line-height: 2;
        font-size: 14px;
    }
    .about-content h1, .about-content h2, .about-content h3 {
        margin-top: 20px;
        margin-bottom: 10px;
        color: var(--color-title);
    }
    .about-content p {
        margin-bottom: 15px;
    }
    .about-content ul, .about-content ol {
        margin-right: 20px;
        margin-bottom: 15px;
    }
    .about-content img {
        max-width: 100%;
        height: auto;
        border-radius: 12px;
        margin: 10px 0;
    }
</style>
