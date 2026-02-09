<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/navasan_service.php';

$navasan = new NavasanService($pdo);

// Sync if needed
$sync_interval = (int)get_setting('api_sync_interval', 10) * 60;
$last_sync = get_setting('last_sync_time', 0);
if (time() - $last_sync > $sync_interval) {
    if ($navasan->syncPrices()) {
        set_setting('last_sync_time', time());
    }
}

$items = $navasan->getDashboardData();

// Fetch categories for filtering
try {
    $categories = $pdo->query("SELECT slug FROM categories")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = ['gold', 'currency', 'coin']; // Fallback
}

$site_title = get_setting('site_title', 'طلا آنلاین');
$site_description = get_setting('site_description', 'مرجع تخصصی قیمت لحظه‌ای طلا، سکه و ارز. مقایسه بهترین پلتفرم‌های خرید و فروش طلا در ایران.');
$site_keywords = get_setting('site_keywords', 'قیمت طلا, قیمت سکه, دلار تهران, خرید طلا, مقایسه قیمت طلا');

// Organize data
$gold_data = null;
$silver_data = null;
$coins = [];

foreach ($items as $item) {
    if ($item['symbol'] == '18ayar') $gold_data = $item;
    if ($item['symbol'] == 'silver') $silver_data = $item;

    // Show in coins list if it's in a managed category (except silver which has its own box)
    if (in_array($item['category'], $categories) && $item['category'] !== 'silver') {
        $coins[] = $item;
    }
}

// Platforms
$stmt = $pdo->query("SELECT * FROM platforms ORDER BY sort_order ASC");
$platforms = $stmt->fetchAll();

// Helpers
function fa_num($num) {
    if ($num === null || $num === '') return '---';
    if (class_exists('NumberFormatter')) {
        $fmt = new NumberFormatter('fa_IR', NumberFormatter::DECIMAL);
        return $fmt->format($num);
    }
    // Simple fallback
    $western = ['0','1','2','3','4','5','6','7','8','9'];
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return str_replace($western, $persian, (string)$num);
}

function fa_price($num) {
    return fa_num($num);
}

function get_trend_arrow($change) {
    if ($change > 0) return '<span class="trend-arrow trend-up"></span>';
    if ($change < 0) return '<span class="trend-arrow trend-down"></span>';
    return '';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title) ?> | قیمت لحظه‌ای طلا، سکه و ارز</title>
    <meta name="description" content="<?= htmlspecialchars($site_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($site_keywords) ?>">
    <link rel="canonical" href="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="og:title" content="<?= htmlspecialchars($site_title) ?> | قیمت لحظه‌ای طلا، سکه و ارز">
    <meta property="og:description" content="<?= htmlspecialchars($site_description) ?>">
    <meta property="og:image" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/assets/images/logo.svg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($site_title) ?> | قیمت لحظه‌ای طلا، سکه و ارز">
    <meta property="twitter:description" content="<?= htmlspecialchars($site_description) ?>">
    <meta property="twitter:image" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/assets/images/logo.svg">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?= htmlspecialchars($site_title) ?>",
      "url": "<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>",
      "description": "<?= htmlspecialchars($site_description) ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "<?= htmlspecialchars($site_title) ?>",
      "url": "<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>",
      "logo": "<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/site/assets/images/logo.svg"
    }
    </script>

    <link rel="stylesheet" href="assets/css/grid.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
    <a href="#main-content" class="skip-link">رفتن به محتوای اصلی</a>
    <h1 class="sr-only"><?= htmlspecialchars($site_title) ?> - مرجع قیمت لحظه‌ای طلا و سکه</h1>
    <div class="main" id="main-content">
        <div class="top-bar mb-10">
            <div class="center d-flex just-between align-center">
                <div class="logo">
                    <svg width="117" height="26" viewBox="0 0 117 26" fill="none" xmlns="http://www.w3.org/2000/svg" style="height: 32px; width: auto;" aria-label="طلا آنلاین" role="img">
                        <path d="M0.371094 16.9111H8.93555V19.5186H0.371094C0.240885 19.5186 0.146484 19.4307 0.0878906 19.2549C0.0292969 19.0791 0 18.734 0 18.2197C0 17.7054 0.0292969 17.3604 0.0878906 17.1846C0.146484 17.0023 0.240885 16.9111 0.371094 16.9111Z" fill="currentColor"/>
                        <path d="M14.3164 14.4014C14.3164 15.0199 14.3685 15.5146 14.4727 15.8857C14.5768 16.2568 14.7884 16.5205 15.1074 16.6768C15.4264 16.833 15.9115 16.9111 16.5625 16.9111H17.0508V19.5186H16.5332C15.7194 19.5186 14.9837 19.3525 14.3262 19.0205C13.6686 18.682 13.2259 18.2165 12.998 17.624C12.8483 18.0212 12.5879 18.363 12.2168 18.6494C11.8522 18.9294 11.4193 19.1442 10.918 19.2939C10.4167 19.4437 9.89583 19.5186 9.35547 19.5186H8.94531C8.8151 19.5186 8.7207 19.4307 8.66211 19.2549C8.60352 19.0791 8.57422 18.734 8.57422 18.2197C8.57422 17.7054 8.60352 17.3604 8.66211 17.1846C8.7207 17.0023 8.8151 16.9111 8.94531 16.9111H9.35547C10.026 16.9111 10.5273 16.833 10.8594 16.6768C11.1979 16.514 11.4225 16.2503 11.5332 15.8857C11.6504 15.5146 11.709 15.0199 11.709 14.4014V12.7412H14.3164V14.4014ZM11.1426 10.1435C10.6634 9.66972 10.6612 8.89644 11.1377 8.41992C11.6142 7.9434 12.3875 7.94561 12.8613 8.42483C13.3313 8.90022 13.3292 9.66597 12.8564 10.1387C12.3837 10.6114 11.618 10.6135 11.1426 10.1435Z" fill="currentColor"/>
                        <path d="M21.5723 11.0615C21.7611 11.8298 21.9108 12.5589 22.0215 13.249C22.1322 13.9326 22.1875 14.5706 22.1875 15.1631C22.1875 16.042 22.028 16.8102 21.709 17.4678C21.39 18.1188 20.8822 18.6234 20.1855 18.9814C19.4954 19.3395 18.5872 19.5186 17.4609 19.5186H17.0508C16.9206 19.5186 16.8262 19.4307 16.7676 19.2549C16.709 19.0791 16.6797 18.734 16.6797 18.2197C16.6797 17.7054 16.709 17.3604 16.7676 17.1846C16.8262 17.0023 16.9206 16.9111 17.0508 16.9111H17.4609C18.2357 16.9111 18.7923 16.7451 19.1309 16.4131C19.4694 16.0745 19.6387 15.5635 19.6387 14.8799C19.6387 14.4827 19.5898 14.0238 19.4922 13.5029C19.401 12.9756 19.2773 12.3864 19.1211 11.7354L21.5723 11.0615ZM19.1016 23.7666C18.6224 23.2928 18.6202 22.5195 19.0967 22.043C19.5732 21.5664 20.3465 21.5687 20.8203 22.0479C21.2903 22.5233 21.2881 23.289 20.8154 23.7617C20.3427 24.2344 19.577 24.2366 19.1016 23.7666ZM15.6836 23.7666C15.2044 23.2928 15.2022 22.5195 15.6787 22.043C16.1552 21.5664 16.9285 21.5687 17.4023 22.0479C17.8723 22.5233 17.8702 23.289 17.3975 23.7617C16.9248 24.2344 16.159 24.2366 15.6836 23.7666Z" fill="currentColor"/>
                        <path d="M24.2285 6.2959L26.6504 5.36816C27.2689 6.83952 27.7604 8.35319 28.125 9.90918C28.4961 11.4587 28.7305 13.0179 28.8281 14.5869L26.3086 14.9092C26.25 13.9521 26.1296 12.9886 25.9473 12.0186C25.765 11.042 25.5273 10.0752 25.2344 9.11816C24.9479 8.15462 24.6126 7.21387 24.2285 6.2959ZM32.1387 5.58301H34.7461V14.8311C34.7461 15.6188 34.9121 16.1624 35.2441 16.4619C35.5762 16.7614 36.1686 16.9111 37.0215 16.9111H37.0312V19.5186H37.0215C35.791 19.5186 34.821 19.2679 34.1113 18.7666C33.4082 18.2653 32.9688 17.5068 32.793 16.4912L32.7832 16.2178C32.2559 16.9795 31.5951 17.6045 30.8008 18.0928C30.0065 18.5745 29.0462 18.9326 27.9199 19.167C26.7936 19.4014 25.4655 19.5186 23.9355 19.5186V16.9111C25.6738 16.9111 27.0964 16.7874 28.2031 16.54C29.3099 16.2861 30.1725 15.876 30.791 15.3096C31.416 14.7432 31.8652 13.988 32.1387 13.0439V5.58301Z" fill="currentColor"/>
                        <path d="M41.5527 11.0615C41.7415 11.8298 41.8913 12.5589 42.002 13.249C42.1126 13.9326 42.168 14.5706 42.168 15.1631C42.168 16.042 42.0085 16.8102 41.6895 17.4678C41.3704 18.1188 40.8626 18.6234 40.166 18.9814C39.4759 19.3395 38.5677 19.5186 37.4414 19.5186H37.0312C36.901 19.5186 36.8066 19.4307 36.748 19.2549C36.6895 19.0791 36.6602 18.734 36.6602 18.2197C36.6602 17.7054 36.6895 17.3604 36.748 17.1846C36.8066 17.0023 36.901 16.9111 37.0312 16.9111H37.4414C38.2161 16.9111 38.7728 16.7451 39.1113 16.4131C39.4499 16.0745 39.6191 15.5635 39.6191 14.8799C39.6191 14.4827 39.5703 14.0238 39.4727 13.5029C39.3815 12.9756 39.2578 12.3864 39.1016 11.7354L41.5527 11.0615ZM38.5059 8.82517C38.0267 8.35136 38.0245 7.57808 38.501 7.10156C38.9775 6.62504 39.7508 6.62725 40.2246 7.10647C40.6946 7.58186 40.6924 8.34761 40.2197 8.82031C39.747 9.29302 38.9813 9.29518 38.5059 8.82517Z" fill="currentColor"/>
                        <path d="M48.079 1.88929C48.2726 1.62327 48.6237 1.50459 48.9163 1.65499C49.2048 1.80329 49.3272 2.15577 49.1661 2.43732C48.9191 2.86892 48.66 3.20134 48.3887 3.43457C48.0241 3.74707 47.5749 3.90332 47.041 3.90332C46.7285 3.90332 46.4193 3.85449 46.1133 3.75684C45.8138 3.65918 45.5371 3.55176 45.2832 3.43457C45.0814 3.34342 44.8893 3.2653 44.707 3.2002C44.5247 3.12858 44.3522 3.09277 44.1895 3.09277C43.8704 3.09277 43.6035 3.21322 43.3887 3.4541C43.2768 3.57952 43.1614 3.71995 43.0425 3.87537C42.8461 4.13207 42.4888 4.21087 42.2129 4.04256C41.9402 3.87618 41.8394 3.52467 42.0103 3.25467C42.2884 2.81498 42.5916 2.46479 42.9199 2.2041C43.3626 1.84603 43.8314 1.66699 44.3262 1.66699C44.5801 1.66699 44.8242 1.70931 45.0586 1.79395C45.2995 1.87207 45.5404 1.96322 45.7812 2.06738C46.0156 2.17155 46.2435 2.2627 46.4648 2.34082C46.6862 2.41895 46.9076 2.45801 47.1289 2.45801C47.3633 2.45801 47.5781 2.37988 47.7734 2.22363C47.8728 2.14418 47.9746 2.03273 48.079 1.88929ZM46.8848 5.58301V19.5186H44.2773V5.58301H46.8848Z" fill="currentColor"/>
                        <path d="M53.4863 6.2959L55.9082 5.36816C56.5267 6.83952 57.0182 8.35319 57.3828 9.90918C57.7539 11.4587 57.9883 13.0179 58.0859 14.5869L55.5664 14.9092C55.5078 13.9521 55.3874 12.9886 55.2051 12.0186C55.0228 11.042 54.7852 10.0752 54.4922 9.11816C54.2057 8.15462 53.8704 7.21387 53.4863 6.2959ZM61.3965 5.58301H64.0039V14.8311C64.0039 15.6188 64.1699 16.1624 64.502 16.4619C64.834 16.7614 65.4264 16.9111 66.2793 16.9111H66.2891V19.5186H66.2793C65.0488 19.5186 64.0788 19.2679 63.3691 18.7666C62.666 18.2653 62.2266 17.5068 62.0508 16.4912L62.041 16.2178C61.5137 16.9795 60.8529 17.6045 60.0586 18.0928C59.2643 18.5745 58.304 18.9326 57.1777 19.167C56.0514 19.4014 54.7233 19.5186 53.1934 19.5186V16.9111C54.9316 16.9111 56.3542 16.7874 57.4609 16.54C58.5677 16.2861 59.4303 15.876 60.0488 15.3096C60.6738 14.7432 61.123 13.988 61.3965 13.0439V5.58301Z" fill="currentColor"/>
                        <path d="M66.3086 19.5186C66.1784 19.5186 66.084 19.4307 66.0254 19.2549C65.9603 19.0791 65.9277 18.734 65.9277 18.2197C65.9277 17.7054 65.9603 17.3604 66.0254 17.1846C66.084 17.0023 66.1784 16.9111 66.3086 16.9111H67.5488C67.6009 16.833 67.653 16.7549 67.7051 16.6768C67.7637 16.5921 67.8158 16.514 67.8613 16.4424V5.58301H70.459V11.6572C70.459 12.0739 70.4427 12.4906 70.4102 12.9072C70.3841 13.3174 70.3288 13.721 70.2441 14.1182C70.9733 13.1351 71.748 12.3929 72.5684 11.8916C73.3887 11.3903 74.2253 11.1396 75.0781 11.1396C75.8854 11.1396 76.5788 11.3219 77.1582 11.6865C77.7441 12.0511 78.1934 12.5394 78.5059 13.1514C78.8249 13.7568 78.9844 14.4307 78.9844 15.1729C78.9844 15.7588 78.89 16.3122 78.7012 16.833C78.5189 17.3538 78.1868 17.8161 77.7051 18.2197C77.2298 18.6234 76.5658 18.9424 75.7129 19.1768C74.86 19.4046 73.7663 19.5186 72.4316 19.5186H66.3086ZM70.6055 16.9111H73.0078C73.457 16.9111 73.8704 16.8883 74.248 16.8428C74.6322 16.7907 74.9642 16.7061 75.2441 16.5889C75.5306 16.4652 75.752 16.2959 75.9082 16.0811C76.0645 15.8597 76.1426 15.5798 76.1426 15.2412C76.1426 14.7855 76.0026 14.4144 75.7227 14.1279C75.4492 13.8415 75.0781 13.6982 74.6094 13.6982C74.2643 13.6982 73.8835 13.8024 73.4668 14.0107C73.0501 14.2126 72.6009 14.5479 72.1191 15.0166C71.6439 15.4854 71.1393 16.1169 70.6055 16.9111Z" fill="currentColor"/>
                        <rect width="18.9091" height="18.9091" transform="matrix(0.866025 0.5 -0.866025 0.5 100.36 7.09094)" fill="#6580E1"/>
                        <path d="M100.36 7.09091V0L88.0783 7.09091L83.9844 16.5455L100.36 7.09091Z" fill="#93AAFC"/>
                        <path d="M100.36 7.09091V0L112.642 7.09091L116.736 16.5455L100.36 7.09091Z" fill="#6580E1"/>
                        <path d="M100.36 26V14.1819L88.0783 7.09094L83.9844 16.5455L100.36 26Z" fill="#F8CA4C"/>
                        <path d="M100.36 26V14.1819L112.642 7.09094L116.736 16.5455L100.36 26Z" fill="#DCAF33"/>
                        <rect width="14.1818" height="14.1818" transform="matrix(0.866025 0.5 -0.866025 0.5 100.36 0)" fill="#FFD35B"/>
                    </svg>
                </div>
                <div class="d-flex align-center gap-15">
                    <button id="theme-toggle" class="theme-btn" aria-label="تغییر تم (روشن/تاریک)">
                        <svg class="sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                        <svg class="moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    </button>
                    <div class="d-flex align-center gap-10 border-right pr-15">
                        <span class="live-pulse"></span>
                        <span id="current-date" class="font-size-0-9 font-bold color-title">
                            <?php
                            if (class_exists('IntlDateFormatter')) {
                                $fmt = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL);
                                echo $fmt->format(new DateTime());
                            } else {
                                echo date('Y-m-d'); // Fallback
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div id="error-banner" class="center mb-20 d-none">
            <div class="error-container block-card">
                <div class="error-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="error-content">
                    <h3 class="error-title">خطا در دریافت اطلاعات</h3>
                    <p class="error-message">متأسفانه در حال حاضر قادر به دریافت اطلاعات از سرور نیستیم. لطفا اتصال اینترنت خود را بررسی کنید.</p>
                </div>
                <button id="reload-btn" class="btn btn-error" aria-label="تلاش مجدد برای بارگذاری اطلاعات">تلاش مجدد</button>
            </div>
        </div>
        <div class="section">
            <div class="center">
                <div class="d-flex-wrap gap-20">
                    <div class="mob-scroll basis500 grow-1 d-flex gap-20">

                        <!-- Gold Summary -->
                        <div class="block-card price-card fade-in-up" id="gold-summary">
                            <div class="card-header d-flex just-between align-center mb-15">
                                <div class="d-flex align-center gap-10">
                                    <div class="asset-icon gold-icon">
                                        <img src="assets/images/gold.svg" alt="طلای ۱۸ عیار">
                                    </div>
                                    <div>
                                        <div class="asset-name color-title font-bold">طلای ۱۸ عیار</div>
                                        <div class="font-size-0-8 color-bright">قیمت لحظه‌ای طلا</div>
                                    </div>
                                </div>
                                <div class="live-tag">
                                    <span class="pulse-dot"></span>
                                    زنده
                                </div>
                            </div>

                            <div class="price-main mb-20">
                                <div class="d-flex align-baseline">
                                    <span class="color-title font-size-2-5 font-bold current-price"><?= fa_price($gold_data['price'] ?? null) ?></span>
                                    <span class="color-bright font-size-1 font-bold mr-10">تومان</span>
                                </div>
                                <div class="trend-badge-wrapper mt-5 d-flex align-center gap-10">
                                    <?php
                                    $change = (float)($gold_data['change'] ?? 0);
                                    $percent = (float)($gold_data['change_percent'] ?? 0);
                                    $badge_class = $change >= 0 ? 'color-green' : 'color-red';
                                    $sign = $change > 0 ? '+' : '';
                                    ?>
                                    <span class="trend-badge change-percent <?= $badge_class ?>"><?= get_trend_arrow($change) ?><?= fa_num($percent) ?>٪</span>
                                    <span class="price-change-val price-change">(<?= $sign . fa_price($change) ?>)</span>
                                </div>
                            </div>

                            <div class="stats-grid">
                                <div class="stats-item">
                                    <div class="stats-label">بیشترین امروز</div>
                                    <div class="stats-value high-price"><?= fa_price($gold_data['high'] ?? null) ?> <span class="font-size-0-7 color-bright">تومان</span></div>
                                </div>
                                <div class="stats-item">
                                    <div class="stats-label">کمترین امروز</div>
                                    <div class="stats-value low-price"><?= fa_price($gold_data['low'] ?? null) ?> <span class="font-size-0-7 color-bright">تومان</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Silver Summary -->
                        <div class="block-card price-card fade-in-up" id="silver-summary" style="animation-delay: 0.1s;">
                            <div class="card-header d-flex just-between align-center mb-15">
                                <div class="d-flex align-center gap-10">
                                    <div class="asset-icon silver-icon">
                                        <img src="assets/images/silver.svg" alt="نقره">
                                    </div>
                                    <div>
                                        <div class="asset-name color-title font-bold">نقره ۹۹۹</div>
                                        <div class="font-size-0-8 color-bright">قیمت لحظه‌ای نقره</div>
                                    </div>
                                </div>
                                <div class="live-tag">
                                    <span class="pulse-dot"></span>
                                    زنده
                                </div>
                            </div>

                            <div class="price-main mb-20">
                                <div class="d-flex align-baseline">
                                    <span class="color-title font-size-2-5 font-bold current-price"><?= fa_price($silver_data['price'] ?? null) ?></span>
                                    <span class="color-bright font-size-1 font-bold mr-10">تومان</span>
                                </div>
                                <div class="trend-badge-wrapper mt-5 d-flex align-center gap-10">
                                    <?php
                                    $change = (float)($silver_data['change'] ?? 0);
                                    $percent = (float)($silver_data['change_percent'] ?? 0);
                                    $badge_class = $change >= 0 ? 'color-green' : 'color-red';
                                    $sign = $change > 0 ? '+' : '';
                                    ?>
                                    <span class="trend-badge change-percent <?= $badge_class ?>"><?= get_trend_arrow($change) ?><?= fa_num($percent) ?>٪</span>
                                    <span class="price-change-val price-change">(<?= $sign . fa_price($change) ?>)</span>
                                </div>
                            </div>

                            <div class="stats-grid">
                                <div class="stats-item">
                                    <div class="stats-label">بیشترین امروز</div>
                                    <div class="stats-value high-price"><?= fa_price($silver_data['high'] ?? null) ?> <span class="font-size-0-7 color-bright">تومان</span></div>
                                </div>
                                <div class="stats-item">
                                    <div class="stats-label">کمترین امروز</div>
                                    <div class="stats-value low-price"><?= fa_price($silver_data['low'] ?? null) ?> <span class="font-size-0-7 color-bright">تومان</span></div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="block-card d-flex-column just-between basis500 chartbox fade-in-up" style="animation-delay: 0.2s;">
                        <div class="d-flex just-between align-start mb-10">
                            <div class="d-flex-wrap gap-10">
                                <div class="mode-toggle">
                                    <button id="gold-chart-btn" class="mode-btn active" aria-label="نمایش نمودار طلا">نمودار طلا</button>
                                    <button id="silver-chart-btn" class="mode-btn" aria-label="نمایش نمودار نقره">نمودار نقره</button>
                                </div>
                                <div class="mode-toggle period-toggle">
                                    <button id="period-7d" class="mode-btn active" aria-label="نمایش ۷ روز اخیر">۷ روز</button>
                                    <button id="period-30d" class="mode-btn" aria-label="نمایش ۳۰ روز اخیر">۳۰ روز</button>
                                    <button id="period-1y" class="mode-btn" aria-label="نمایش ۱ سال اخیر">۱ سال</button>
                                </div>
                            </div>

                            <div class="d-flex gap-20">
                                <div class="">
                                    <div class="font-size-0-9">بالاترین قیمت</div>
                                    <div class=""><span class="font-size-1-2 color-title chart-high-price"><?= fa_price($gold_data['high'] ?? null) ?></span> <span class="color-bright">تومان</span></div>
                                </div>
                                <div class="">
                                    <div class="font-size-0-9">پایین‌ترین قیمت</div>
                                    <div class=""><span class="font-size-1-2 color-title chart-low-price"><?= fa_price($gold_data['low'] ?? null) ?></span> <span class="color-bright">تومان</span></div>
                                </div>
                            </div>
                        </div>

                        <div id="chart" class="chart"></div>

                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="center d-flex-wrap gap-20 just-center">

                <div class="block-card fade-in-up" style="animation-delay: 0.3s;">
                    <div class="d-flex-wrap just-between align-center mb-20 gap-15">
                        <div class="d-flex gap-10 align-center">
                            <div class="height-40 width-40 radius-100 border d-flex just-center align-center"><img src="assets/images/road-wayside.svg" alt="مقایسه پلتفرم‌ها"></div>
                            <div>
                                <h2 class="color-title">مقایسه پلتفرم های طلا آنلاین</h2>
                                <span class="font-size-0-9">با بررسی قیمت ها بهترین پتلفرم را برای معامله انتخاب کنید</span>
                            </div>
                        </div>
                        <div class="search-box" role="search">
                            <input type="text" id="platform-search" placeholder="جستجوی پلتفرم..." class="search-input" aria-label="جستجوی نام پلتفرم طلا">
                            <span class="search-icon-wrapper" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </span>
                            <div id="search-announcement" class="sr-only" aria-live="polite"></div>
                        </div>
                    </div>
                    <div class="table-box">
                        <table class="full-width">
                            <thead>
                                <tr>
                                    <th>لوگو</th>
                                    <th class="sortable" data-sort="name">
                                        <div class="d-flex align-center">نام پلتفرم <span class="sort-icon"></span></div>
                                    </th>
                                    <th class="sortable" data-sort="buy_price">
                                        <div class="d-flex align-center">قیمت خرید (تومان) <span class="sort-icon"></span></div>
                                    </th>
                                    <th class="sortable" data-sort="sell_price">
                                        <div class="d-flex align-center">قیمت فروش (تومان) <span class="sort-icon"></span></div>
                                    </th>
                                    <th class="sortable" data-sort="fee">
                                        <div class="d-flex align-center">کارمزد <span class="sort-icon"></span></div>
                                    </th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody id="platforms-table-body">
                                <?php foreach ($platforms as $p): ?>
                                <tr>
                                    <td>
                                        <div class="brand-logo">
                                            <img src="<?= htmlspecialchars($p['logo']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="line20">
                                            <div class="color-title"><?= htmlspecialchars($p['name']) ?></div>
                                            <div class="font-size-0-8"><?= htmlspecialchars($p['en_name']) ?></div>
                                        </div>
                                    </td>
                                    <td class="font-size-1-2 color-title"><?= fa_price($p['buy_price']) ?></td>
                                    <td class="font-size-1-2 color-title"><?= fa_price($p['sell_price']) ?></td>
                                    <td class="font-size-1-2" dir="ltr"><?= fa_num($p['fee']) ?>٪</td>
                                    <td>
                                        <span class="status-badge <?= $p['status'] === 'مناسب خرید' ? 'buy' : 'sell' ?>">
                                            <?= htmlspecialchars($p['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= htmlspecialchars($p['link']) ?>" class="btn" target="_blank" rel="noopener noreferrer" aria-label="خرید طلا از <?= htmlspecialchars($p['name']) ?> (در پنجره جدید)">خرید طلا</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="block-card order-0 fade-in-up" style="animation-delay: 0.4s;">
                    <div id="coins-list">
                        <?php foreach ($coins as $c): ?>
                        <div class="coin-item">
                            <div class="d-flex align-center gap-10">
                                <div class="brand-logo">
                                    <img src="<?= htmlspecialchars($c['logo']) ?>" alt="<?= htmlspecialchars($c['name']) ?>">
                                </div>
                                <div class="line24">
                                    <div class="color-title font-size-1"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="font-size-0-8"><?= htmlspecialchars($c['en_name']) ?></div>
                                </div>
                            </div>

                            <div class="line24 text-left">
                                <div class=""><span class="color-title font-size-1-2 font-bold"><?= fa_price($c['price']) ?></span> <span class="color-bright font-size-0-8">تومان</span></div>
                                <div class="<?= (float)$c['change_percent'] >= 0 ? 'color-green' : 'color-red' ?> font-size-0-8 mt-4">
                                    <?= get_trend_arrow($c['change_percent']) ?><?= fa_num($c['change_percent']) ?>٪
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.__INITIAL_STATE__ = {
            platforms: <?= json_encode($platforms) ?>,
            coins: <?= json_encode($coins) ?>,
            summary: {
                gold: <?= json_encode($gold_data) ?>,
                silver: <?= json_encode($silver_data) ?>
            }
        };
    </script>
    <script src="assets/js/charts.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
