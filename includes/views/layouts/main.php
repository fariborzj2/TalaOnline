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
            <script>
                window.__AUTH_STATE__ = {
                    isLoggedIn: <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>,
                    user: <?= isset($_SESSION['user_id']) ? json_encode(['name' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']]) : 'null' ?>
                };
            </script>
            <div class="center d-flex-wrap gap-md align-stretch main-layout">
                <div class="main-content d-column gap-md grow-8 overflow-hidden basis-700" >
                    <div class="hader">
                        <div class="d-flex-wrap gap-1 just-between align-center">
                            <div class="font-size-3 font-bold"><?= htmlspecialchars($h1_title ?? $page_title ?? 'طلا آنلاین') ?></div>
                            <div class="d-flex gap-1">
                                <div class="border radius-10 pl-1 pr-1 pt-05 pb-05 d-flex align-center gap-05 bg-block text-title pointer">
                                    <i data-lucide="bell" class="icon-size-3"></i>
                                </div>

                                <div class="border radius-10 pl-1 pr-1-5 pt-05 pb-05 d-flex align-center gap-05 bg-block text-title pointer hover-bg-secondary transition-all" id="user-menu-btn">
                                    <i data-lucide="user" class="icon-size-3"></i>
                                    <span class="font-bold font-size-1" id="user-menu-text">
                                        <?= isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_name']) : 'ورود / عضویت' ?>
                                    </span>
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
                        <h1 class="sr-only"><?= htmlspecialchars($page_title ?? 'طلا آنلاین') ?></h1>
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

    <!-- Auth & Profile Modals -->
    <div id="auth-modal" class="modal-overlay d-none">
        <div class="modal-content bg-block radius-24 shadow-lg overflow-hidden basis-400">
            <div class="pd-md border-bottom d-flex just-between align-center">
                <div class="d-flex gap-1" id="auth-tabs">
                    <button class="font-bold font-size-3 pointer active" data-tab="login">ورود</button>
                    <button class="font-bold font-size-3 pointer" data-tab="register">ثبت نام</button>
                </div>
                <button class="close-modal pointer"><i data-lucide="x" class="icon-size-4"></i></button>
            </div>

            <div class="pd-md">
                <form id="login-form" class="auth-form d-column gap-1-5">
                    <div class="d-column gap-05">
                        <label class="font-size-1 font-bold pr-1">ایمیل یا شماره موبایل</label>
                        <div class="input-item">
                            <i data-lucide="mail" class="text-gray icon-size-3"></i>
                            <input type="text" name="email" placeholder="مثلاً example@mail.com" dir="ltr" class="text-left" required>
                        </div>
                    </div>
                    <div class="d-column gap-05">
                        <label class="font-size-1 font-bold pr-1">کلمه عبور</label>
                        <div class="input-item">
                            <i data-lucide="lock" class="text-gray icon-size-3"></i>
                            <input type="password" name="password" placeholder="********" dir="ltr" class="text-left" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary radius-12 just-center w-full mt-1">ورود به حساب</button>
                </form>

                <form id="register-form" class="auth-form d-column gap-1-5 d-none">
                    <div class="d-column gap-05">
                        <label class="font-size-1 font-bold pr-1">نام و نام خانوادگی</label>
                        <div class="input-item">
                            <i data-lucide="user" class="text-gray icon-size-3"></i>
                            <input type="text" name="name" placeholder="علی علوی" required>
                        </div>
                    </div>
                    <div class="d-column gap-05">
                        <label class="font-size-1 font-bold pr-1">آدرس ایمیل</label>
                        <div class="input-item">
                            <i data-lucide="mail" class="text-gray icon-size-3"></i>
                            <input type="email" name="email" placeholder="example@mail.com" dir="ltr" class="text-left" required>
                        </div>
                    </div>
                    <div class="d-column gap-05">
                        <label class="font-size-1 font-bold pr-1">شماره موبایل</label>
                        <div class="input-item">
                            <i data-lucide="phone" class="text-gray icon-size-3"></i>
                            <input type="text" name="phone" placeholder="09123456789" dir="ltr" class="text-left" required>
                        </div>
                    </div>
                    <div class="d-column gap-05">
                        <label class="font-size-1 font-bold pr-1">کلمه عبور</label>
                        <div class="input-item">
                            <i data-lucide="lock" class="text-gray icon-size-3"></i>
                            <input type="password" name="password" placeholder="********" dir="ltr" class="text-left" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary radius-12 just-center w-full mt-1">ایجاد حساب کاربری</button>
                </form>

                <div class="auth-divider my-2 relative text-center">
                    <span class="bg-block px-1 text-gray font-size-1 relative z-10">یا ادامه با</span>
                    <div class="divider-line absolute top-50 w-full bg-border" style="height:1px; top:50%;"></div>
                </div>

                <button class="btn btn-secondary radius-12 just-center w-full gap-1 border">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="18" height="18" alt="Google">
                    <span class="font-bold">ورود با گوگل</span>
                </button>
            </div>
        </div>
    </div>

    <div id="profile-modal" class="modal-overlay d-none">
        <div class="modal-content bg-block radius-24 shadow-lg overflow-hidden basis-350">
            <div class="pd-md border-bottom d-flex just-between align-center">
                <h3 class="font-bold font-size-4">پروفایل کاربری</h3>
                <button class="close-modal pointer"><i data-lucide="x" class="icon-size-4"></i></button>
            </div>
            <div class="pd-md d-column align-center gap-1-5">
                <div class="w-20 h-20 radius-50 bg-secondary d-flex align-center just-center border">
                    <i data-lucide="user" class="icon-size-6 text-primary"></i>
                </div>
                <div class="text-center">
                    <h4 class="font-bold font-size-4 text-title">کاربر مهمان</h4>
                    <p class="text-gray font-size-2">guest@tala.online</p>
                </div>

                <div class="w-full d-column gap-1 border-top pt-1-5">
                    <a href="#" class="d-flex align-center gap-1 pd-1 radius-12 hover-bg-secondary text-title transition-all">
                        <i data-lucide="settings" class="icon-size-4"></i>
                        <span class="font-bold">تنظیمات حساب</span>
                    </a>
                    <a href="#" class="d-flex align-center gap-1 pd-1 radius-12 hover-bg-secondary text-title transition-all">
                        <i data-lucide="heart" class="icon-size-4"></i>
                        <span class="font-bold">علاقه‌مندی‌ها</span>
                    </a>
                    <button class="d-flex align-center gap-1 pd-1 radius-12 hover-bg-error text-error w-full text-right transition-all pointer" id="logout-btn">
                        <i data-lucide="log-out" class="icon-size-4"></i>
                        <span class="font-bold">خروج از حساب</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($load_charts)): ?>
    <script src="/assets/js/charts.js" defer></script>
    <?php endif; ?>
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
