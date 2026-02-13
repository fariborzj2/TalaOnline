<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title ?? 'طلا آنلاین') ?> | قیمت لحظه‌ای طلا، سکه و ارز</title>
    <meta name="description" content="<?= htmlspecialchars($meta_description ?? $site_description ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta_keywords ?? $site_keywords ?? '') ?>">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/grid.css">
    <link rel="stylesheet" href="assets/css/font.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="og:title" content="<?= htmlspecialchars($site_title ?? '') ?> | قیمت لحظه‌ای طلا، سکه و ارز">
    <meta property="og:description" content="<?= htmlspecialchars($site_description ?? '') ?>">
    <meta property="og:image" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/assets/images/logo.svg">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?= htmlspecialchars($site_title ?? 'طلا آنلاین') ?>",
      "url": "<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>",
      "description": "<?= htmlspecialchars($site_description ?? '') ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
</head>
<body>
    <?php
    $current_uri = $_SERVER['REQUEST_URI'];
    $current_path = parse_url($current_uri, PHP_URL_PATH);
    // Simple normalization to match the hrefs
    $current_path = '/' . ltrim($current_path, '/');
    if ($current_path !== '/') {
        $current_path = rtrim($current_path, '/');
    }
    ?>
    <main class="app">
        <div class="side-menu">
            <div class="logo"><img src="assets/images/logo.svg" alt="طلا آنلاین"></div>
            <ul>
                <li><a href="/" class="<?= $current_path == '/' ? 'active' : '' ?>"><i data-lucide="house" class="w-6 h-6"></i></a></li>
                <li><a href="/calculator" class="<?= $current_path == '/calculator' ? 'active' : '' ?>"><i data-lucide="calculator" class="w-6 h-6"></i></a></li>
                <li><a href="/about-us" class="<?= $current_path == '/about-us' ? 'active' : '' ?>"><i data-lucide="book-open-text" class="w-6 h-6"></i></a></li>
                <li><a href="/feedback" class="<?= $current_path == '/feedback' ? 'active' : '' ?>"><i data-lucide="message-square-more" class="w-6 h-6"></i></a></li>
            </ul>
        </div>

        <div class="container">
            <div class="center d-flex-wrap gap-md align-stretch main-layout">
                <div class="main-content d-column gap-md grow-8 overflow-hidden basis-700" >
                    <div class="hader">
                        <div class="d-flex-wrap gap-1 just-between align-center">
                            <h1 class="font-size-3"><?= htmlspecialchars($h1_title ?? $page_title ?? 'طلا آنلاین') ?></h1>
                            <div class="d-flex gap-1">
                                <div class="border radius-10 pl-1 pr-1 pt-05 pb-05 d-flex align-center gap-05 bg-block text-title">
                                    <i data-lucide="bell" class="icon-size-3"></i>
                                </div>

                                <div class="border radius-10 pl-1-5 pr-1-5 pt-05 pb-05 d-flex align-center gap-05 bg-block text-title">
                                    <div class="pulse-container ml-05">
                                        <span class="pulse-dot"></span>
                                    </div>
                                    <i data-lucide="calendar-days" class="icon-size-3"></i>
                                    <span><?= jalali_date() ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?= $content ?>
                </div>

                <?= View::renderSection('sidebar') ?>

            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
    <!-- Detail Modal -->
    <div id="detail-modal" class="modal-overlay d-none">
        <div class="modal-content bg-block radius-16 shadow-lg">
            <div class="modal-header d-flex just-between align-center pd-md border-bottom">
                <div class="d-flex align-center gap-1">
                    <div class="modal-icon w-12 h-12 border radius-12 p-05 bg-secondary d-flex align-center just-center">
                        <img id="modal-asset-icon" src="assets/images/gold/gold.png" alt="">
                    </div>
                    <div>
                        <h2 id="modal-title" class="text-title font-size-2">---</h2>
                        <span id="modal-symbol" class="text-gray font-size-0-9">---</span>
                    </div>
                </div>
                <button id="close-modal" class="btn btn-secondary btn-sm radius-10">
                    <i data-lucide="x" class="icon-size-4"></i>
                </button>
            </div>
            <div class="modal-body pd-md">
                <div class="d-flex-wrap just-between align-center mb-1">
                    <div class="line-height-1-5">
                        <strong id="modal-price" class="font-size-6 text-title">---</strong>
                        <div class="d-flex align-center gap-1 mt-05" dir="ltr">
                            <span id="modal-change-percent" class="d-flex align-center gap-05 font-bold">
                                <span>---</span>
                                <i data-lucide="arrow-up" class="icon-size-1"></i>
                            </span>
                            <span id="modal-change-amount" class="text-gray">---</span>
                        </div>
                    </div>
                    <div class="pill-toggle-group" id="modal-period-toggle">
                        <button class="pill-btn active" data-period="7d">۷ روز</button>
                        <button class="pill-btn" data-period="30d">۳۰ روز</button>
                        <button class="pill-btn" data-period="1y">۱ سال</button>
                    </div>
                </div>

                <div id="modal-chart-container" style="height: 350px; position: relative;">
                    <div id="modal-chart"></div>
                </div>

                <div class="d-flex just-between mt-1 pt-1 border-top">
                     <div class="d-flex-wrap gap-05">
                         <span class="text-gray">بالاترین (۲۴ساعته):</span>
                         <strong id="modal-high" class="text-success">---</strong>
                     </div>
                     <div class="d-flex-wrap gap-05">
                         <span class="text-gray">پایین‌ترین (۲۴ساعته):</span>
                         <strong id="modal-low" class="text-error">---</strong>
                     </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-content {
            width: 100%;
            max-width: 800px;
            animation: modalFadeIn 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .d-none { display: none !important; }
        .asset-item { cursor: pointer; transition: transform 0.2s; }
    </style>

    <script src="assets/js/charts.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
