<?php
/**
 * Application Routes
 */

require_once __DIR__ . '/navasan_service.php';

$router->add('/', function() {
    global $pdo;
    $items = [];
    if ($pdo) {
        $navasan = new NavasanService($pdo);
        $items = $navasan->getDashboardData();
    } else {
        // Mock data for verification if DB is not available
        $items = [
            ['symbol' => '18ayar', 'price' => 19853180, 'change_percent' => 3.04, 'change_amount' => 580000, 'category' => 'gold', 'name' => 'طلای ۱۸ عیار'],
            ['symbol' => 'silver', 'price' => 1234567, 'change_percent' => -0.5, 'change_amount' => 6000, 'category' => 'silver', 'name' => 'نقره ۹۹۹'],
            ['symbol' => 'sekeh', 'price' => 193000000, 'change_percent' => 1.54, 'change_amount' => 2900000, 'category' => 'coin', 'name' => 'سکه امامی'],
            ['symbol' => 'rob', 'price' => 56500000, 'change_percent' => 3.15, 'change_amount' => 1700000, 'category' => 'coin', 'name' => 'ربع سکه'],
        ];
    }

    $categories_data = [];
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
            $categories_data = $stmt->fetchAll();
        } catch (Exception $e) {}
    } else {
        $categories_data = [
            ['slug' => 'gold', 'name' => 'طلا و جواهرات', 'en_name' => 'gold market', 'icon' => 'coins'],
            ['slug' => 'coin', 'name' => 'مسکوکات طلا', 'en_name' => 'gold coins', 'icon' => 'circle-dollar-sign'],
        ];
    }

    $gold_data = null;
    $silver_data = null;

    // Group items by category
    $grouped_items = [];
    $home_limit = (int)get_setting('home_category_limit', 5);

    foreach ($categories_data as $cat) {
        $grouped_items[$cat['slug']] = [
            'info' => $cat,
            'items' => []
        ];
    }

    foreach ($items as $item) {
        if ($item['symbol'] == '18ayar') $gold_data = $item;
        if ($item['symbol'] == 'silver') $silver_data = $item;

        if (isset($grouped_items[$item['category']])) {
            if (count($grouped_items[$item['category']]['items']) < $home_limit) {
                $grouped_items[$item['category']]['items'][] = $item;
            }
        }
    }

    $platforms = [];
    $summary_items = [];
    $chart_items = [];
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM platforms WHERE is_active = 1 ORDER BY sort_order ASC");
            $platforms = $stmt->fetchAll();

            // Check for items marked as show_in_summary
            $stmt = $pdo->query("SELECT symbol FROM items WHERE show_in_summary = 1 AND is_active = 1 ORDER BY sort_order ASC LIMIT 4");
            $summary_symbols = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Check for items marked as show_chart
            $stmt = $pdo->query("SELECT symbol FROM items WHERE show_chart = 1 AND is_active = 1 ORDER BY sort_order ASC");
            $chart_symbols = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($items as $item) {
                if (in_array($item['symbol'], $summary_symbols)) {
                    $summary_items[] = $item;
                }
                if (in_array($item['symbol'], $chart_symbols)) {
                    $chart_items[] = $item;
                }
            }
        } catch (Exception $e) {}
    } else {
        // Mock chart items
        foreach ($items as $item) {
            if (in_array($item['symbol'], ['18ayar', 'sekeh'])) {
                $chart_items[] = $item;
            }
        }
    }

    // Fallback if no items marked for summary
    if (empty($summary_items)) {
        if ($gold_data) $summary_items[] = $gold_data;
        if ($silver_data) $summary_items[] = $silver_data;
    }

    if (empty($platforms)) {
        $platforms = [
            ['name' => 'گرمی', 'logo' => 'assets/images/platforms/gerami.png', 'buy_price' => 19800000, 'sell_price' => 19700000, 'fee' => 0.5, 'status' => 'active', 'link' => '#', 'en_name' => 'Gerami'],
            ['name' => 'میلی', 'logo' => 'assets/images/platforms/milli.png', 'buy_price' => 19900000, 'sell_price' => 19800000, 'fee' => 0.1, 'status' => 'active', 'link' => '#', 'en_name' => 'Milli'],
        ];
    }

    return View::renderPage('home', [
        'is_home' => true,
        'site_title' => get_setting('site_title', 'طلا آنلاین'),
        'site_description' => get_setting('site_description', 'مرجع تخصصی قیمت لحظه‌ای طلا، سکه و ارز.'),
        'site_keywords' => get_setting('site_keywords', 'قیمت طلا, قیمت سکه'),
        'gold_data' => $gold_data,
        'silver_data' => $silver_data,
        'summary_items' => $summary_items,
        'chart_items' => $chart_items,
        'grouped_items' => $grouped_items,
        'platforms' => $platforms
    ]);
});

$router->add('/item/:symbol', function($params) {
    return View::renderPage('item_details', [
        'symbol' => $params['symbol'],
        'page_title' => 'جزئیات ' . $params['symbol']
    ]);
});

$router->add('/feedback', function() {
    global $pdo;
    $message = '';
    $success = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['message'] ?? '';

        if (!empty($name) && !empty($body)) {
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO feedbacks (name, email, subject, message) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $subject, $body]);
                    $message = 'پیام شما با موفقیت ارسال شد. با تشکر از بازخورد شما.';
                    $success = true;
                } catch (Exception $e) {
                    $message = 'متاسفانه خطایی در ارسال پیام رخ داد. لطفا دوباره تلاش کنید.';
                }
            } else {
                $message = 'خطا در اتصال به پایگاه داده. لطفا بعدا تلاش کنید.';
            }
        } else {
            $message = 'لطفا تمامی فیلدهای ضروری (نام و پیام) را پر کنید.';
        }
    }

    return View::renderPage('feedback', [
        'page_title' => 'تماس با ما / ارسال بازخورد',
        'message' => $message,
        'success' => $success
    ]);
});

$router->add('/about-us', function() {
    return View::renderPage('about', [
        'page_title' => 'درباره ما',
        'content' => get_setting('about_us_content', 'لطفاً محتوای این صفحه را از پنل مدیریت تنظیم کنید.')
    ]);
});

$router->add('/:slug', function($params) {
    global $pdo;
    $slug = $params['slug'];
    $category = null;
    $items = [];
    $faqs = [];

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
            $stmt->execute([$slug]);
            $category = $stmt->fetch();

            if ($category) {
                // Fetch Items
                $navasan = new NavasanService($pdo);
                $all_items = $navasan->getDashboardData();
                foreach ($all_items as $item) {
                    if ($item['category'] === $slug) {
                        $items[] = $item;
                    }
                }

                // Fetch FAQs
                $stmt = $pdo->prepare("SELECT * FROM category_faqs WHERE category_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$category['id']]);
                $faqs = $stmt->fetchAll();
            }
        } catch (Exception $e) {}
    }



    if (!$category) {
        // Fallback to 404 if not a category
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }

    return View::renderPage('category', [
        'category' => $category,
        'items' => $items,
        'faqs' => $faqs,
        'page_title' => $category['page_title'] ?: $category['name'],
        'h1_title' => $category['h1_title'],
        'meta_description' => $category['meta_description'],
        'meta_keywords' => $category['meta_keywords'],
        'site_title' => $category['page_title'] ?: ($category['name'] . ' | ' . get_setting('site_title', 'طلا آنلاین')),
    ]);
});
