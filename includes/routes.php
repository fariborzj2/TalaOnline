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
        'load_charts' => true,
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

// Blog Routes
$router->add('/blog', function() {
    global $pdo;
    $posts = [];
    $categories = [];
    $featured_posts = [];

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = (int)get_setting('blog_posts_per_page', '10');
    $offset = ($page - 1) * $per_page;
    $total_pages = 1;

    if ($pdo) {
        try {
            // Get total count for pagination
            $total_posts = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
            $total_pages = ceil($total_posts / $per_page);

            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
                                 FROM blog_posts p
                                 LEFT JOIN blog_categories c ON p.category_id = c.id
                                 WHERE p.status = 'published'
                                 ORDER BY p.created_at DESC
                                 LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll();

            $categories = $pdo->query("SELECT * FROM blog_categories ORDER BY sort_order ASC")->fetchAll();

            $featured_limit = (int)get_setting('blog_featured_count', '3');
            $stmt_featured = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
                                          FROM blog_posts p
                                          LEFT JOIN blog_categories c ON p.category_id = c.id
                                          WHERE p.status = 'published' AND p.is_featured = 1
                                          ORDER BY p.created_at DESC LIMIT :limit");
            $stmt_featured->bindValue(':limit', $featured_limit, PDO::PARAM_INT);
            $stmt_featured->execute();
            $featured_posts = $stmt_featured->fetchAll();
        } catch (Exception $e) {}
    }

    return View::renderPage('blog', [
        'page_title' => 'وبلاگ طلا آنلاین',
        'posts' => $posts,
        'categories' => $categories,
        'featured_posts' => $featured_posts,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'site_title' => get_setting('blog_main_title', 'وبلاگ و اخبار طلا و ارز') . ' | ' . get_setting('site_title', 'طلا آنلاین'),
        'meta_description' => get_setting('blog_main_description', 'آخرین اخبار، مقالات تخصصی و تحلیل‌های بازار طلا، سکه و ارز را در وبلاگ طلا آنلاین بخوانید.'),
        'meta_keywords' => get_setting('blog_main_keywords', 'اخبار طلا, تحلیل بازار, مقالات آموزشی طلا'),
        'breadcrumbs' => [
            ['name' => 'وبلاگ', 'url' => '/blog']
        ]
    ]);
});

$router->add('/blog/:category_slug', function($params) {
    global $pdo;
    $slug = $params['category_slug'];
    $posts = [];
    $category = null;
    $categories = [];

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = (int)get_setting('blog_posts_per_page', '10');
    $offset = ($page - 1) * $per_page;
    $total_pages = 1;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM blog_categories WHERE slug = ?");
            $stmt->execute([$slug]);
            $category = $stmt->fetch();

            if ($category) {
                // Get total count for category
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM blog_posts p
                                           INNER JOIN blog_post_categories pc ON p.id = pc.post_id
                                           WHERE p.status = 'published' AND pc.category_id = ?");
                $stmt_count->execute([$category['id']]);
                $total_posts = $stmt_count->fetchColumn();
                $total_pages = ceil($total_posts / $per_page);

                $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
                                     FROM blog_posts p
                                     INNER JOIN blog_post_categories pc ON p.id = pc.post_id
                                     LEFT JOIN blog_categories c ON p.category_id = c.id
                                     WHERE p.status = 'published' AND pc.category_id = :cat_id
                                     ORDER BY p.created_at DESC
                                     LIMIT :limit OFFSET :offset");
                $stmt->bindValue(':cat_id', $category['id'], PDO::PARAM_INT);
                $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $posts = $stmt->fetchAll();
            }

            $categories = $pdo->query("SELECT * FROM blog_categories ORDER BY sort_order ASC")->fetchAll();
        } catch (Exception $e) {}
    }

    if (!$category) {
        http_response_code(404);
        echo "404 Category Not Found";
        exit;
    }

    return View::renderPage('blog', [
        'page_title' => 'دسته‌بندی: ' . $category['name'],
        'posts' => $posts,
        'categories' => $categories,
        'current_category' => $category,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'site_title' => $category['name'] . ' | وبلاگ طلا آنلاین',
        'meta_description' => $category['description'],
        'breadcrumbs' => [
            ['name' => 'وبلاگ', 'url' => '/blog'],
            ['name' => $category['name'], 'url' => '/blog/' . $category['slug']]
        ]
    ]);
});

$router->add('/blog/:category_slug/:post_slug', function($params) {
    global $pdo;
    $category_slug = $params['category_slug'];
    $post_slug = $params['post_slug'];
    $post = null;
    $related_posts = [];

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
                                 FROM blog_posts p
                                 INNER JOIN blog_post_categories pc ON p.id = pc.post_id
                                 INNER JOIN blog_categories current_c ON pc.category_id = current_c.id
                                 LEFT JOIN blog_categories c ON p.category_id = c.id
                                 WHERE p.slug = ? AND p.status = 'published' AND current_c.slug = ?");
            $stmt->execute([$post_slug, $category_slug]);
            $post = $stmt->fetch();

            if ($post) {
                // Update views
                $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);

                // Fetch all categories for this post
                $stmt_cats = $pdo->prepare("SELECT c.* FROM blog_categories c INNER JOIN blog_post_categories pc ON c.id = pc.category_id WHERE pc.post_id = ? ORDER BY c.sort_order ASC");
                $stmt_cats->execute([$post['id']]);
                $post['all_categories'] = $stmt_cats->fetchAll();

                // Fetch FAQs
                $stmt_faqs = $pdo->prepare("SELECT * FROM blog_post_faqs WHERE post_id = ? ORDER BY sort_order ASC");
                $stmt_faqs->execute([$post['id']]);
                $post['faqs'] = $stmt_faqs->fetchAll();

                // Related posts
                $related_count = (int)get_setting('blog_related_count', '3');
                if ($post['category_id'] && $related_count > 0) {
                    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug
                                         FROM blog_posts p
                                         LEFT JOIN blog_categories c ON p.category_id = c.id
                                         WHERE p.status = 'published' AND p.category_id = :cat_id AND p.id != :post_id
                                         ORDER BY p.created_at DESC LIMIT :limit");
                    $stmt->bindValue(':cat_id', $post['category_id'], PDO::PARAM_INT);
                    $stmt->bindValue(':post_id', $post['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':limit', $related_count, PDO::PARAM_INT);
                    $stmt->execute();
                    $related_posts = $stmt->fetchAll();
                }
            }
        } catch (Exception $e) {}
    }

    if (!$post) {
        http_response_code(404);
        echo "404 Post Not Found";
        exit;
    }

    $canonical_url = get_base_url() . '/blog/' . ($post['category_slug'] ?: 'uncategorized') . '/' . $post['slug'];

    return View::renderPage('blog_post', [
        'post' => $post,
        'all_categories' => $post['all_categories'] ?? [],
        'faqs' => $post['faqs'] ?? [],
        'related_posts' => $related_posts,
        'canonical_url' => $canonical_url,
        'hide_layout_h1' => true,
        'page_title' => $post['title'],
        'site_title' => ($post['meta_title'] ?: $post['title']) . ' | وبلاگ طلا آنلاین',
        'meta_description' => $post['meta_description'] ?: $post['excerpt'],
        'meta_keywords' => $post['meta_keywords'],
        'og_image' => $post['thumbnail'] ? (get_base_url() . '/' . ltrim($post['thumbnail'], '/')) : null,
        'breadcrumbs' => [
            ['name' => 'وبلاگ', 'url' => '/blog'],
            ['name' => $post['category_name'] ?: 'بدون دسته', 'url' => $post['category_slug'] ? '/blog/' . $post['category_slug'] : '#'],
            ['name' => $post['title'], 'url' => '/blog/' . $post['category_slug'] . '/' . $post['slug']]
        ]
    ]);
});

$router->add('/robots.txt', function() {
    header('Content-Type: text/plain');
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Allow: /assets/\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /api/\n";
    echo "Disallow: /installer.php\n\n";
    echo "Sitemap: " . get_base_url() . "/sitemap.xml\n";
    exit;
});

// Sitemap Routes
$router->add('/sitemap.xml', function() {
    global $pdo;
    require_once __DIR__ . '/../site/sitemap.php';
    exit;
});

$router->add('/sitemap-pages.xml', function() {
    global $pdo;
    require_once __DIR__ . '/../site/sitemap-pages.php';
    exit;
});

$router->add('/sitemap-categories.xml', function() {
    global $pdo;
    require_once __DIR__ . '/../site/sitemap-categories.php';
    exit;
});

$router->add('/sitemap-items.xml', function() {
    global $pdo;
    require_once __DIR__ . '/../site/sitemap-items.php';
    exit;
});

$router->add('/sitemap-posts.xml', function() {
    global $pdo;
    require_once __DIR__ . '/../site/sitemap-posts.php';
    exit;
});

$router->add('/:category/:slug', function($params) {
    global $pdo;
    $category_slug = $params['category'];
    $slug = $params['slug'];

    if (!$pdo) {
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }

    try {
        // Find the item with the given slug that belongs to the given category
        $stmt = $pdo->prepare("SELECT i.* FROM items i
                               WHERE (i.slug = ? OR (i.slug IS NULL AND i.symbol = ?))
                               AND i.category = ?");
        $stmt->execute([$slug, $slug, $category_slug]);
        $item_db = $stmt->fetch();

        if ($item_db) {
            $navasan = new NavasanService($pdo);
            $all_items = $navasan->getDashboardData();

            // Create lookup for all items to quickly find current and related items
            $items_lookup = [];
            foreach ($all_items as $it) {
                $items_lookup[$it['symbol']] = $it;
            }

            $item_data = array_merge($item_db, $items_lookup[$item_db['symbol']] ?? []);
            $related_item = $items_lookup[$item_db['related_item_symbol'] ?? ''] ?? null;

            // Fetch FAQs
            $faqs = [];
            try {
                $stmt = $pdo->prepare("SELECT * FROM item_faqs WHERE item_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$item_db['id']]);
                $faqs = $stmt->fetchAll();
            } catch (Exception $e) {}

            $og_image = !empty($item_data['logo']) ? (get_base_url() . '/' . ltrim($item_data['logo'], '/')) : null;

            $stmt_cat = $pdo->prepare("SELECT name FROM categories WHERE slug = ?");
            $stmt_cat->execute([$category_slug]);
            $cat_data = $stmt_cat->fetch();
            $category_name = $cat_data ? $cat_data['name'] : $category_slug;

            return View::renderPage('asset', [
                'item' => $item_data,
                'load_charts' => true,
                'related_item' => $related_item,
                'faqs' => $faqs,
                'hide_layout_h1' => true,
                'page_title' => $item_data['page_title'] ?: $item_data['name'],
                'h1_title' => $item_data['h1_title'] ?: $item_data['name'],
                'meta_description' => $item_data['meta_description'],
                'meta_keywords' => $item_data['meta_keywords'],
                'og_image' => $og_image,
                'breadcrumbs' => [
                    ['name' => $category_name, 'url' => '/' . $category_slug],
                    ['name' => $item_data['name'], 'url' => '/' . $category_slug . '/' . ($item_data['slug'] ?: $item_data['symbol'])]
                ],
                'site_title' => $item_data['page_title'] ?: ($item_data['name'] . ' | ' . get_setting('site_title', 'طلا آنلاین')),
            ]);
        }
    } catch (Exception $e) {}

    http_response_code(404);
    echo "404 Not Found";
    exit;
});

$router->add('/:slug', function($params) {
    global $pdo;
    $slug = $params['slug'];

    if (!$pdo) {
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }

    // 1. Check if it's a category
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $category = $stmt->fetch();

        if ($category) {
            // Fetch Items
            $navasan = new NavasanService($pdo);
            $all_items = $navasan->getDashboardData();
            $items = [];
            foreach ($all_items as $item) {
                if ($item['category'] === $slug) {
                    $items[] = $item;
                }
            }

            // Fetch FAQs
            $stmt = $pdo->prepare("SELECT * FROM category_faqs WHERE category_id = ? ORDER BY sort_order ASC");
            $stmt->execute([$category['id']]);
            $faqs = $stmt->fetchAll();

            $og_image = !empty($category['logo']) ? (get_base_url() . '/' . ltrim($category['logo'], '/')) : null;

            return View::renderPage('category', [
                'category' => $category,
                'load_charts' => true,
                'items' => $items,
                'faqs' => $faqs,
                'page_title' => $category['page_title'] ?: $category['name'],
                'h1_title' => $category['h1_title'],
                'meta_description' => $category['meta_description'],
                'meta_keywords' => $category['meta_keywords'],
                'og_image' => $og_image,
                'breadcrumbs' => [
                    ['name' => $category['name'], 'url' => '/' . $category['slug']]
                ],
                'site_title' => $category['page_title'] ?: ($category['name'] . ' | ' . get_setting('site_title', 'طلا آنلاین')),
            ]);
        }
    } catch (Exception $e) {}

    // 2. Check if it's an asset (item)
    try {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE slug = ? OR (slug IS NULL AND symbol = ?)");
        $stmt->execute([$slug, $slug]);
        $item_db = $stmt->fetch();

        if ($item_db) {
            $navasan = new NavasanService($pdo);
            $all_items = $navasan->getDashboardData();

            // Create lookup for all items to quickly find current and related items
            $items_lookup = [];
            foreach ($all_items as $it) {
                $items_lookup[$it['symbol']] = $it;
            }

            $item_data = array_merge($item_db, $items_lookup[$item_db['symbol']] ?? []);
            $related_item = $items_lookup[$item_db['related_item_symbol'] ?? ''] ?? null;

            // Fetch FAQs
            $faqs = [];
            try {
                $stmt = $pdo->prepare("SELECT * FROM item_faqs WHERE item_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$item_db['id']]);
                $faqs = $stmt->fetchAll();
            } catch (Exception $e) {}

            $og_image = !empty($item_data['logo']) ? (get_base_url() . '/' . ltrim($item_data['logo'], '/')) : null;

            $stmt_cat = $pdo->prepare("SELECT name FROM categories WHERE slug = ?");
            $stmt_cat->execute([$item_db['category']]);
            $cat_data = $stmt_cat->fetch();
            $category_name = $cat_data ? $cat_data['name'] : $item_db['category'];

            return View::renderPage('asset', [
                'item' => $item_data,
                'load_charts' => true,
                'related_item' => $related_item,
                'faqs' => $faqs,
                'hide_layout_h1' => true,
                'page_title' => $item_data['page_title'] ?: $item_data['name'],
                'h1_title' => $item_data['h1_title'] ?: $item_data['name'],
                'meta_description' => $item_data['meta_description'],
                'meta_keywords' => $item_data['meta_keywords'],
                'og_image' => $og_image,
                'breadcrumbs' => [
                    ['name' => $category_name, 'url' => '/' . $item_db['category']],
                    ['name' => $item_data['name'], 'url' => '/' . $item_db['category'] . '/' . ($item_data['slug'] ?: $item_data['symbol'])]
                ],
                'site_title' => $item_data['page_title'] ?: ($item_data['name'] . ' | ' . get_setting('site_title', 'طلا آنلاین')),
            ]);
        }
    } catch (Exception $e) {}

    // Fallback to 404
    http_response_code(404);
    echo "404 Not Found";
    exit;
});
