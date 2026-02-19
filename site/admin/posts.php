<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_login();

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'مقاله با موفقیت حذف شد.';
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE blog_posts SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $message = 'وضعیت مقاله با موفقیت تغییر کرد.';
    } elseif ($action === 'toggle_featured') {
        $id = $_POST['id'];
        $featured = $_POST['featured'];
        $stmt = $pdo->prepare("UPDATE blog_posts SET is_featured = ? WHERE id = ?");
        $stmt->execute([$featured, $id]);
        $message = 'وضعیت ویژه با موفقیت تغییر کرد.';
    }
}

// Pagination & Sorting
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// Allowed sort fields
$allowed_sorts = ['created_at', 'views', 'title'];
if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';

// Total posts for pagination
$total_posts = $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug,
    (SELECT GROUP_CONCAT(DISTINCT cat.name) FROM blog_categories cat INNER JOIN blog_post_categories pc ON cat.id = pc.category_id WHERE pc.post_id = p.id) as all_categories
    FROM blog_posts p
    LEFT JOIN blog_categories c ON p.category_id = c.id
    ORDER BY p.$sort $order
    LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

function sort_link($field, $label) {
    global $sort, $order, $page;
    $new_order = ($sort === $field && $order === 'DESC') ? 'ASC' : 'DESC';
    $icon = '';
    if ($sort === $field) {
        $icon = '<i data-lucide="chevron-' . ($order === 'DESC' ? 'down' : 'up') . '" class="w-3 h-3 inline"></i>';
    }
    return '<a href="?sort=' . $field . '&order=' . $new_order . '&page=' . $page . '" class="flex items-center gap-1 hover:text-indigo-600 transition-colors">' . $label . ' ' . $icon . '</a>';
}

$page_title = 'مدیریت مقالات وبلاگ';
$page_subtitle = 'نوشتن، ویرایش و مدیریت محتوای وبلاگ';

$header_action = '<a href="post_edit.php" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن مقاله جدید</a>';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="mb-6">
        <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 flex items-center gap-3 text-emerald-700">
            <div class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="glass-card rounded-xl overflow-hidden border border-slate-200">
    <div class="px-8 py-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                <i data-lucide="newspaper" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">لیست مقالات</h2>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative group w-full md:w-auto">
                <input type="text" id="tableSearch" placeholder="جستجو در این صفحه..." class="text-xs !pr-12 !py-2 w-full md:w-64 border-slate-200 focus:border-indigo-500 transition-all">
                <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400">کل: <?= number_format($total_posts) ?></span>
                <span class="w-px h-3 bg-slate-100"></span>
                <span class="text-[10px] font-bold text-indigo-600">این صفحه: <span id="itemCount"><?= count($posts) ?></span></span>
            </div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full admin-table">
            <thead>
                <tr>
                    <th><?= sort_link('title', 'عنوان مقاله') ?></th>
                    <th>دسته‌بندی</th>
                    <th class="text-center">وضعیت</th>
                    <th class="text-center">ویژه</th>
                    <th><?= sort_link('created_at', 'تاریخ انتشار') ?></th>
                    <th class="text-center"><?= sort_link('views', 'بازدید') ?></th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($posts as $post): ?>
                <tr class="hover:bg-slate-50/50 transition-colors group">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg bg-slate-100 overflow-hidden shrink-0 border border-slate-100">
                                <?php if ($post['thumbnail']): ?>
                                    <img src="../<?= htmlspecialchars($post['thumbnail']) ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-300">
                                        <i data-lucide="image" class="w-5 h-5"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="font-black text-slate-900 line-clamp-1"><?= htmlspecialchars($post['title']) ?></p>
                                <p class="text-[10px] font-bold text-slate-400 ltr-input">/blog/<?= htmlspecialchars($post['category_slug'] ?? 'uncategorized') ?>/<?= htmlspecialchars($post['slug']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            <?php
                            $all_cats = !empty($post['all_categories']) ? explode(',', $post['all_categories']) : [];
                            if (empty($all_cats)) $all_cats = [$post['category_name'] ?? 'بدون دسته'];
                            foreach ($all_cats as $idx => $cat_name):
                                $is_primary = ($cat_name === $post['category_name']);
                            ?>
                                <span class="px-2 py-0.5 <?= $is_primary ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-slate-100 text-slate-600 border-slate-200' ?> rounded text-[10px] font-black border">
                                    <?= htmlspecialchars($cat_name) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="text-center">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="status" value="<?= $post['status'] === 'published' ? 'draft' : 'published' ?>">
                            <button type="submit" class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black <?= $post['status'] === 'published' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200' ?>">
                                <?= $post['status'] === 'published' ? 'منتشر شده' : 'پیش‌نویس' ?>
                            </button>
                        </form>
                    </td>
                    <td class="text-center">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_featured">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="featured" value="<?= $post['is_featured'] ? 0 : 1 ?>">
                            <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center mx-auto transition-all <?= $post['is_featured'] ? 'text-amber-500 bg-amber-50 border border-amber-100' : 'text-slate-300 hover:text-slate-400' ?>">
                                <i data-lucide="star" class="w-4 h-4 <?= $post['is_featured'] ? 'fill-amber-500' : '' ?>"></i>
                            </button>
                        </form>
                    </td>
                    <td>
                        <span class="text-[10px] font-bold text-slate-500"><?= jalali_time_tag($post['created_at']) ?></span>
                    </td>
                    <td class="text-center font-bold text-slate-600 text-[11px]">
                        <?= number_format($post['views']) ?>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="post_edit.php?id=<?= $post['id'] ?>" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                <i data-lucide="edit-3" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                            </a>
                            <form method="POST" class="inline" onsubmit="handleDelete(event, this, 'مقاله')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-rose-600 hover:border-rose-100 hover:bg-rose-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                    <i data-lucide="trash-2" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                <i data-lucide="inbox" class="w-12 h-12 stroke-1"></i>
                                <p class="font-bold">هنوز هیچ مقاله‌ای منتشر نشده است.</p>
                                <a href="post_edit.php" class="text-indigo-600 text-xs font-black hover:underline">اولین مقاله خود را بنویسید</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="px-8 py-4 bg-slate-50/30 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4">
        <span class="text-[10px] font-bold text-slate-400">نمایش <?= count($posts) ?> از <?= number_format($total_posts) ?> مقاله (صفحه <?= $page ?> از <?= $total_pages ?>)</span>
        <div class="flex items-center gap-1.5">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-lg text-slate-400 hover:text-indigo-600 hover:border-indigo-200 transition-all">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <a href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="w-8 h-8 flex items-center justify-center <?= $i == $page ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 shadow-sm' ?> rounded-lg font-black text-[11px] transition-all">
                        <?= $i ?>
                    </a>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <span class="text-slate-300 px-1">...</span>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-lg text-slate-400 hover:text-indigo-600 hover:border-indigo-200 transition-all">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    const searchInput = document.getElementById('tableSearch');
    const tableBody = document.querySelector('.admin-table tbody');
    const itemCountSpan = document.getElementById('itemCount');

    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        let filteredCount = 0;
        const rows = Array.from(tableBody.querySelectorAll('tr:not(.no-results)'));

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
                filteredCount++;
            } else {
                row.style.display = 'none';
            }
        });
        itemCountSpan.innerText = filteredCount;
    });

    async function handleDelete(event, form, name) {
        event.preventDefault();
        const confirmed = await showConfirm(`آیا از حذف این ${name} اطمینان دارید؟`);
        if (confirmed) {
            form.submit();
        }
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
