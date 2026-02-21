<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_permission("assets.view");

// Schema Self-Healing
try {
    $columns = $pdo->query("DESCRIBE items")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_active', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }
    if (!in_array('category', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN category VARCHAR(50) DEFAULT 'gold'");
    }
    if (!in_array('show_in_summary', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN show_in_summary TINYINT(1) DEFAULT 0");
    }
    if (!in_array('show_chart', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN show_chart TINYINT(1) DEFAULT 0");
    }
    if (!in_array('slug', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN slug VARCHAR(100) DEFAULT NULL AFTER symbol");
    }
    if (!in_array('h1_title', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN h1_title VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('page_title', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN page_title VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('meta_description', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN meta_description TEXT DEFAULT NULL");
    }
    if (!in_array('meta_keywords', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN meta_keywords TEXT DEFAULT NULL");
    }
    if (!in_array('long_description', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN long_description TEXT DEFAULT NULL");
    }
    if (!in_array('related_item_symbol', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN related_item_symbol VARCHAR(50) DEFAULT NULL");
    }
    if (!in_array('updated_at', $columns)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
} catch (Exception $e) {}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $symbol = $_POST['symbol'] ?? '';
        $name = $_POST['name'] ?? '';
        $en_name = $_POST['en_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $manual_price = $_POST['manual_price'] ?? '';
        $is_manual = isset($_POST['is_manual']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $show_in_summary = isset($_POST['show_in_summary']) ? 1 : 0;
        $show_chart = isset($_POST['show_chart']) ? 1 : 0;
        $category = $_POST['category'] ?? 'gold';
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        // Handle Image Upload
        $logo = $_POST['current_logo'] ?? '';
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_upload($_FILES['logo_file']);
            if ($uploaded_path) {
                $logo = $uploaded_path;
            }
        } elseif (!empty($_POST['logo_url'])) {
            $logo = $_POST['logo_url'];
        }

        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO items (symbol, name, en_name, description, logo, manual_price, is_manual, is_active, show_in_summary, show_chart, category, sort_order, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $stmt->execute([$symbol, $name, $en_name, $description, $logo, $manual_price, $is_manual, $is_active, $show_in_summary, $show_chart, $category, $sort_order]);
                $message = 'دارایی جدید با موفقیت اضافه شد.';
            } catch (Exception $e) {
                $error = 'خطا در افزودن دارایی: ' . $e->getMessage();
            }
        } else {
            $stmt = $pdo->prepare("UPDATE items SET symbol = ?, name = ?, en_name = ?, description = ?, logo = ?, manual_price = ?, is_manual = ?, is_active = ?, show_in_summary = ?, show_chart = ?, category = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$symbol, $name, $en_name, $description, $logo, $manual_price, $is_manual, $is_active, $show_in_summary, $show_chart, $category, $sort_order, $id]);
            $message = 'دارایی با موفقیت بروزرسانی شد.';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'دارایی با موفقیت حذف شد.';
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE items SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $message = 'وضعیت با موفقیت تغییر کرد.';
    } elseif ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        foreach ($ids as $index => $id) {
            $stmt = $pdo->prepare("UPDATE items SET sort_order = ? WHERE id = ?");
            $stmt->execute([$index, $id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

$items = $pdo->query("SELECT i.*, p.price as api_price FROM items i LEFT JOIN prices_cache p ON i.symbol = p.symbol ORDER BY i.sort_order ASC")->fetchAll();

// Fetch categories for mapping and dropdowns
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
} catch (Exception $e) {
    $categories = [];
}
$cat_map = [];
foreach ($categories as $cat) {
    $cat_map[$cat['slug']] = $cat['name'];
}

$page_title = 'مدیریت ارزها';
$page_subtitle = 'مدیریت کامل ارزها، دسته‌بندی‌ها و قیمت‌های دستی';

$header_action = '<a href="item_edit.php" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن دارایی جدید</a>';

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

<?php if ($error): ?>
    <div class="mb-6">
        <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 flex items-center gap-3 text-rose-700">
            <div class="w-8 h-8 bg-rose-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $error ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="glass-card rounded-xl overflow-hidden border border-slate-200">
    <div class="px-8 py-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                <i data-lucide="list" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">لیست ارزها</h2>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative group w-full md:w-auto">
                <input type="text" id="tableSearch" placeholder="جستجو در ارزها..." class="text-xs !pr-12 !py-2 w-full md:w-64 border-slate-200 focus:border-indigo-500 transition-all">
                <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <select id="tableCategory" class="text-xs !py-2 border-slate-200 focus:border-indigo-500 w-full md:w-auto">
                <option value="all">همه دسته‌ها</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="tableSort" class="text-xs !py-2 border-slate-200 focus:border-indigo-500 w-full md:w-auto">
                <option value="sort_order">ترتیب نمایش</option>
                <option value="name">نام (الفبا)</option>
                <option value="price_desc">بیشترین قیمت</option>
                <option value="price_asc">کمترین قیمت</option>
            </select>
            <span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-2 rounded-lg border border-slate-100">تعداد: <span id="itemCount"><?= count($items) ?></span></span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full admin-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th class="w-16">ترتیب</th>
                    <th>دارایی</th>
                    <th>دسته</th>
                    <th>قیمت نمایشی</th>
                    <th class="text-center">بازدید</th>
                    <th class="text-center">خلاصه</th>
                    <th class="text-center">نمودار</th>
                    <th class="text-center">وضعیت</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-slate-50/50 transition-colors group cursor-move" data-id="<?= $item['id'] ?>" data-category="<?= $item['category'] ?? 'gold' ?>">
                    <td class="text-center text-slate-300">
                        <i data-lucide="grip-vertical" class="w-4 h-4 handle cursor-grab"></i>
                    </td>
                    <td class="text-center font-black text-slate-400 row-order"><?= $item['sort_order'] ?></td>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-slate-100 p-2 flex items-center justify-center shrink-0">
                                <img src="../<?= htmlspecialchars($item['logo']) ?>" alt="" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <p class="font-black text-slate-900"><?= htmlspecialchars($item['name'] ?? '') ?></p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter"><?= htmlspecialchars($item['symbol'] ?? '') ?></p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                        $item_cat = $item['category'] ?? 'gold';
                        $cat_name = $cat_map[$item_cat] ?? $item_cat;
                        ?>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-black border border-slate-200">
                            <?= htmlspecialchars($cat_name) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($item['is_manual']): ?>
                            <div class="flex flex-col">
                                <span class="text-[9px] text-slate-400 line-through font-bold"><?= number_format((float)$item['api_price']) ?></span>
                                <span class="text-sm font-black text-indigo-600"><?= number_format((float)$item['manual_price']) ?> <small class="text-[9px] text-slate-400">تومان</small></span>
                            </div>
                        <?php else: ?>
                            <span class="text-sm font-black text-slate-900"><?= number_format((float)$item['api_price']) ?> <small class="text-[9px] text-slate-400">تومان</small></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="text-xs font-black text-slate-600"><?= number_format($item['views'] ?? 0) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($item['show_in_summary']): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-600 border border-indigo-100">
                                نمایش در بالا
                            </span>
                        <?php else: ?>
                            <span class="text-[10px] text-slate-300 font-bold">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($item['show_chart']): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-600 border border-amber-100">
                                در نمودار
                            </span>
                        <?php else: ?>
                            <span class="text-[10px] text-slate-300 font-bold">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php $active = $item['is_active'] ?? 1; ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="status" value="<?= $active ? 0 : 1 ?>">
                            <button type="submit" class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black <?= $active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200' ?>">
                                <?= $active ? 'فعال' : 'غیرفعال' ?>
                            </button>
                        </form>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="item_edit.php?id=<?= $item['id'] ?>" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                <i data-lucide="edit-3" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                            </a>
                            <form method="POST" class="inline" onsubmit="handleDelete(event, this, 'دارایی')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-rose-600 hover:border-rose-100 hover:bg-rose-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                    <i data-lucide="trash-2" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Search and Filter Logic
    const searchInput = document.getElementById('tableSearch');
    const sortSelect = document.getElementById('tableSort');
    const categorySelect = document.getElementById('tableCategory');
    const tableBody = document.querySelector('.admin-table tbody');
    const originalRows = Array.from(tableBody.querySelectorAll('tr'));
    const itemCountSpan = document.getElementById('itemCount');

    function updateTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const sortBy = sortSelect.value;
        const filterCat = categorySelect.value;

        let filteredRows = originalRows.filter(row => {
            const text = row.innerText.toLowerCase();
            const matchesSearch = text.includes(searchTerm);
            const matchesCat = filterCat === 'all' || row.dataset.category === filterCat;
            return matchesSearch && matchesCat;
        });

        // Sort
        filteredRows.sort((a, b) => {
            if (sortBy === 'name') {
                const nameA = a.querySelector('td:nth-child(3) p:first-child').innerText;
                const nameB = b.querySelector('td:nth-child(3) p:first-child').innerText;
                return nameA.localeCompare(nameB, 'fa');
            } else if (sortBy === 'price_desc' || sortBy === 'price_asc') {
                const priceA = parseFloat(a.querySelector('td:nth-child(5) .font-black').innerText.replace(/,/g, '')) || 0;
                const priceB = parseFloat(b.querySelector('td:nth-child(5) .font-black').innerText.replace(/,/g, '')) || 0;
                return sortBy === 'price_desc' ? priceB - priceA : priceA - priceB;
            } else {
                const orderA = parseInt(a.querySelector('td:first-child').innerText) || 0;
                const orderB = parseInt(b.querySelector('td:first-child').innerText) || 0;
                return orderA - orderB;
            }
        });

        // Re-render
        tableBody.innerHTML = '';
        filteredRows.forEach(row => tableBody.appendChild(row));
        itemCountSpan.innerText = filteredRows.length;
    }

    searchInput.addEventListener('input', updateTable);
    sortSelect.addEventListener('change', updateTable);
    categorySelect.addEventListener('change', updateTable);

    // Initialize Sortable
    new Sortable(tableBody, {
        handle: '.handle',
        animation: 150,
        ghostClass: 'bg-indigo-50',
        onEnd: function() {
            const ids = Array.from(tableBody.querySelectorAll('tr')).map(tr => tr.dataset.id);
            const formData = new FormData();
            formData.append('action', 'reorder');
            ids.forEach(id => formData.append('ids[]', id));

            fetch('items.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Update visual order numbers
                    tableBody.querySelectorAll('tr').forEach((tr, index) => {
                        tr.querySelector('.row-order').innerText = index;
                    });
                }
            });
        }
    });

    async function handleDelete(event, form, name) {
        event.preventDefault();
        const confirmed = await showConfirm(`آیا از حذف ${name} اطمینان دارید؟ این عمل غیرقابل بازگشت است.`);
        if (confirmed) {
            form.submit();
        }
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
