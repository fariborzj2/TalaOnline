<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title ?? 'طلا آنلاین') ?> | قیمت لحظه‌ای طلا، سکه و ارز</title>
    <meta name="description" content="<?= htmlspecialchars($meta_description ?? $site_description ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta_keywords ?? $site_keywords ?? '') ?>">
    <link rel="canonical" href="<?= $canonical_url ?? get_current_url() ?>">

    <link rel="preload" href="/assets/fonts/estedad/Estedad-FD-Regular.woff2" as="font" type="font/woff2" crossorigin fetchpriority="high">
    <link rel="preload" href="/assets/fonts/estedad/Estedad-FD-Medium.woff2" as="font" type="font/woff2" crossorigin fetchpriority="high">
    <link rel="preload" href="/assets/fonts/estedad/Estedad-FD-SemiBold.woff2" as="font" type="font/woff2" crossorigin fetchpriority="high">
    <link rel="preload" href="/assets/fonts/estedad/Estedad-FD-Bold.woff2" as="font" type="font/woff2" crossorigin fetchpriority="high">
    <link rel="preload" href="/assets/fonts/estedad/Estedad-FD-ExtraBold.woff2" as="font" type="font/woff2" crossorigin fetchpriority="high">

    <?php if (isset($og_image)): ?>
    <link rel="preload" href="<?= $og_image ?>" as="image" fetchpriority="high">
    <?php endif; ?>

    <style>
        /* CSS Inlining for Performance */
        <?php echo file_get_contents(__DIR__ . '/../../../site/assets/css/font.css'); ?>
        <?php echo file_get_contents(__DIR__ . '/../../../site/assets/css/grid.css'); ?>
        <?php echo file_get_contents(__DIR__ . '/../../../site/assets/css/style.css'); ?>
    </style>

    <script src="/assets/js/vendor/lucide.js" defer></script>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="طلا آنلاین">
    <meta property="og:url" content="<?= get_current_url() ?>">
    <meta property="og:title" content="<?= htmlspecialchars($site_title ?? '') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_description ?? $site_description ?? '') ?>">
    <meta property="og:image" content="<?= $og_image ?? (get_base_url() . "/assets/images/logo.svg") ?>">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= get_current_url() ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($site_title ?? '') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($meta_description ?? $site_description ?? '') ?>">
    <meta name="twitter:image" content="<?= $og_image ?? (get_base_url() . "/assets/images/logo.svg") ?>">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?= htmlspecialchars($site_title ?? 'طلا آنلاین') ?>",
      "url": "<?= get_base_url() ?>",
      "description": "<?= htmlspecialchars($meta_description ?? $site_description ?? '') ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= get_base_url() ?>/?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "خانه",
          "item": "<?= get_base_url() ?>/"
        }<?php foreach ($breadcrumbs as $i => $bc): ?>,
        {
          "@type": "ListItem",
          "position": <?= $i + 2 ?>,
          "name": "<?= htmlspecialchars($bc['name']) ?>",
          "item": "<?= get_base_url() ?><?= $bc['url'] ?>"
        }<?php endforeach; ?>
      ]
    }
    </script>
    <?php endif; ?>
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
            <div class="logo"><img src="/assets/images/logo.svg" alt="طلا آنلاین" width="30" height="35"></div>
            <ul>
                <li><a href="/" class="<?= $current_path == '/' ? 'active' : '' ?>" aria-label="خانه"><i data-lucide="house" class="w-6 h-6"></i></a></li>
                <li><a href="/blog" class="<?= strpos($current_path, '/blog') === 0 ? 'active' : '' ?>" aria-label="وبلاگ"><i data-lucide="newspaper" class="w-6 h-6"></i></a></li>
                <li><a href="/calculator" class="<?= $current_path == '/calculator' ? 'active' : '' ?>" aria-label="ماشین حساب"><i data-lucide="calculator" class="w-6 h-6"></i></a></li>
                <li><a href="/about-us" class="<?= $current_path == '/about-us' ? 'active' : '' ?>" aria-label="درباره ما"><i data-lucide="book-open-text" class="w-6 h-6"></i></a></li>
                <li><a href="/feedback" class="<?= $current_path == '/feedback' ? 'active' : '' ?>" aria-label="ارسال بازخورد"><i data-lucide="message-square-more" class="w-6 h-6"></i></a></li>
            </ul>
        </div>

        <div class="container">
            <div class="center d-flex-wrap gap-md align-stretch main-layout">
                <div class="main-content d-column gap-md grow-8 overflow-hidden basis-700" >
                    <div class="hader">
                        <div class="d-flex-wrap gap-1 just-between align-center">
                            <div class="font-size-3 font-bold"><?= htmlspecialchars($h1_title ?? $page_title ?? 'طلا آنلاین') ?></div>
                            <div class="d-flex gap-1">
                                <div class="border radius-10 pl-1 pr-1 pt-05 pb-05 d-flex align-center gap-05 bg-block text-title">
                                    <i data-lucide="bell" class="icon-size-3"></i>
                                </div>

                                <div class="border radius-10 pl-1-5 pr-1-5 pt-05 pb-05 d-flex align-center gap-05 bg-block text-title">
                                    <div class="pulse-container ml-05">
                                        <span class="pulse-dot"></span>
                                    </div>
                                    <i data-lucide="calendar-days" class="icon-size-3"></i>
                                    <span><?= jalali_time_tag() ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($hide_layout_h1)): ?>
                        <h1 class="d-none"><?= htmlspecialchars($h1_title ?? $page_title ?? 'طلا آنلاین') ?></h1>
                    <?php endif; ?>

                    <?= $content ?>
                </div>

                <?= View::renderSection('sidebar') ?>

            </div>
        </div>
    </main>

    <style>
        .d-none { display: none !important; }
        .asset-item { transition: transform 0.2s; }
        .asset-item:hover { transform: translateY(-2px); }
    </style>

    <?php if (!empty($load_charts)): ?>
    <script src="/assets/js/charts.js" defer></script>
    <?php endif; ?>
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
