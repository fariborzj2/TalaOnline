<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_login();

// Schema Self-Healing
try {
    $pdo->query("SELECT is_active FROM items LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE items ADD COLUMN is_active TINYINT(1) DEFAULT 1");
}
try {
    $pdo->query("SELECT category FROM items LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE items ADD COLUMN category VARCHAR(50) DEFAULT 'gold'");
}

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
                $stmt = $pdo->prepare("INSERT INTO items (symbol, name, en_name, description, logo, manual_price, is_manual, is_active, category, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$symbol, $name, $en_name, $description, $logo, $manual_price, $is_manual, $is_active, $category, $sort_order]);
                $message = 'دارایی جدید با موفقیت اضافه شد.';
            } catch (Exception $e) {
                $error = 'خطا در افزودن دارایی: ' . $e->getMessage();
            }
        } else {
            $stmt = $pdo->prepare("UPDATE items SET symbol = ?, name = ?, en_name = ?, description = ?, logo = ?, manual_price = ?, is_manual = ?, is_active = ?, category = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$symbol, $name, $en_name, $description, $logo, $manual_price, $is_manual, $is_active, $category, $sort_order, $id]);
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
    }
}

$items = $pdo->query("SELECT i.*, p.price as api_price FROM items i LEFT JOIN prices_cache p ON i.symbol = p.symbol ORDER BY i.sort_order ASC")->fetchAll();

$page_title = 'مدیریت ارزها';
$page_subtitle = 'مدیریت کامل ارزها، دسته‌بندی‌ها و قیمت‌های دستی';

$header_action = '<button onclick="openAddModal()" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن دارایی جدید</button>';

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
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative group">
                <input type="text" id="tableSearch" placeholder="جستجو در ارزها..." class="text-xs pr-12 !py-2 w-full md:w-64 border-slate-200 focus:border-indigo-500 transition-all">
                <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <select id="tableCategory" class="text-xs !py-2 border-slate-200 focus:border-indigo-500 w-full md:w-auto">
                <option value="all">همه دسته‌ها</option>
                <option value="gold">طلا</option>
                <option value="coin">سکه</option>
                <option value="currency">ارز</option>
                <option value="silver">نقره</option>
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
                    <th class="w-16">ترتیب</th>
                    <th class="w-20">لوگو</th>
                    <th>نام و عنوان</th>
                    <th>دسته</th>
                    <th>نماد API</th>
                    <th>قیمت نمایشی</th>
                    <th class="text-center">وضعیت</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-slate-50/50 transition-colors group" data-category="<?= $item['category'] ?? 'gold' ?>">
                    <td class="text-center font-black text-slate-400"><?= $item['sort_order'] ?></td>
                    <td>
                        <div class="w-10 h-10 rounded-lg bg-slate-100 p-2 flex items-center justify-center">
                            <img src="../<?= htmlspecialchars($item['logo']) ?>" alt="" class="w-full h-full object-contain">
                        </div>
                    </td>
                    <td>
                        <p class="font-black text-slate-900"><?= htmlspecialchars($item['name']) ?></p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter"><?= htmlspecialchars($item['en_name']) ?></p>
                    </td>
                    <td>
                        <?php
                        $cat_map = ['gold' => 'طلا', 'coin' => 'سکه', 'currency' => 'ارز', 'silver' => 'نقره'];
                        $item_cat = $item['category'] ?? 'gold';
                        $cat_name = $cat_map[$item_cat] ?? $item_cat;
                        ?>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-black border border-slate-200">
                            <?= $cat_name ?>
                        </span>
                    </td>
                    <td>
                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] font-black border border-indigo-100 ltr-input">
                            <?= htmlspecialchars($item['symbol']) ?>
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
                        <?php $active = $item['is_active'] ?? 1; ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="status" value="<?= $active ? 0 : 1 ?>">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black <?= $active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' ?>"></span>
                                <?= $active ? 'فعال' : 'غیرفعال' ?>
                            </button>
                        </form>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn" onclick='editItem(<?= json_encode($item) ?>)'>
                                <i data-lucide="edit-3" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('آیا از حذف این دارایی اطمینان دارید؟')">
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

<!-- Item Modal -->
<div id="itemModal" class="hidden fixed inset-0 z-[1000] bg-slate-900/40 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-xl p-6 md:p-8 transform transition-all animate-modal-up modal-container">
        <div class="flex items-center justify-between border-b border-slate-50 pb-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                    <i data-lucide="package" class="w-6 h-6" id="modalIcon"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900" id="modalTitle">افزودن دارایی جدید</h2>
                    <p class="text-[10px] text-slate-400 font-bold mt-1">تنظیمات قیمت، مشخصات و دسته‌بندی</p>
                </div>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 bg-slate-50 text-slate-400 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="item-id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label>نام فارسی دارایی</label>
                    <input type="text" name="name" id="item-name" required placeholder="مثلاً طلا 18 عیار">
                </div>
                <div class="form-group">
                    <label>نام انگلیسی (EN Name)</label>
                    <input type="text" name="en_name" id="item-en_name" class="ltr-input" placeholder="مثلاً Gold 18K">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label>نماد در API (نوسان)</label>
                    <input type="text" name="symbol" id="item-symbol" required class="ltr-input" placeholder="18ayar">
                </div>
                <div class="form-group">
                    <label>دسته‌بندی</label>
                    <select name="category" id="item-category">
                        <option value="gold">طلا</option>
                        <option value="coin">سکه</option>
                        <option value="currency">ارز</option>
                        <option value="silver">نقره</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>لوگوی دارایی</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="file-input-wrapper">
                        <div class="file-input-custom">
                            <span class="file-name-label text-[11px] text-slate-400 truncate">انتخاب تصویر...</span>
                            <i data-lucide="upload-cloud" class="w-4 h-4 text-slate-400"></i>
                            <input type="file" name="logo_file" class="file-input-real">
                        </div>
                        <p class="text-[9px] text-slate-400 mt-1">آپلود مستقیم تصویر (PNG/JPG)</p>
                    </div>
                    <div class="input-icon-wrapper">
                        <span class="icon"><i data-lucide="link" class="w-3.5 h-3.5"></i></span>
                        <input type="text" name="logo_url" id="item-logo" class="ltr-input text-xs !py-2.5" placeholder="یا لینک تصویر...">
                    </div>
                </div>
                <input type="hidden" name="current_logo" id="item-current_logo">
            </div>

            <div class="form-group mb-4">
                <label>توضیح کوتاه</label>
                <textarea name="description" id="item-description" rows="2" placeholder="توضیحات کوتاهی در مورد این دارایی..."></textarea>
            </div>

            <div class="p-4 bg-slate-50 rounded-lg border border-slate-100 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-black text-slate-800 text-xs">تنظیمات پیشرفته</h4>
                    <div class="flex gap-4">
                        <label class="relative inline-flex items-center cursor-pointer group">
                            <input type="checkbox" name="is_manual" id="item-is_manual" class="sr-only peer">
                            <div class="toggle-dot"></div>
                            <span class="mr-2 text-[10px] font-black text-slate-600">قیمت دستی</span>
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer group">
                            <input type="checkbox" name="is_active" id="item-is_active" class="sr-only peer" checked>
                            <div class="toggle-dot toggle-emerald"></div>
                            <span class="mr-2 text-[10px] font-black text-slate-600">فعال</span>
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group mb-0">
                        <label class="text-[10px] text-slate-400">قیمت دستی (تومان)</label>
                        <input type="text" name="manual_price" id="item-manual_price" class="ltr-input" placeholder="0">
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-[10px] text-slate-400">ترتیب نمایش</label>
                        <input type="number" name="sort_order" id="item-sort_order" value="0">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-v3 btn-v3-primary flex-grow">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    ذخیره اطلاعات دارایی
                </button>
                <button type="button" class="btn-v3 btn-v3-outline" onclick="closeModal()">انصراف</button>
            </div>
        </form>
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
                const priceA = parseFloat(a.querySelector('td:nth-child(6) .font-black').innerText.replace(/,/g, '')) || 0;
                const priceB = parseFloat(b.querySelector('td:nth-child(6) .font-black').innerText.replace(/,/g, '')) || 0;
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

    function openAddModal() {
        document.getElementById('formAction').value = 'add';
        document.getElementById('modalTitle').innerText = 'افزودن دارایی جدید';
        document.getElementById('modalIcon').setAttribute('data-lucide', 'plus');
        document.getElementById('item-id').value = '';
        document.getElementById('item-name').value = '';
        document.getElementById('item-en_name').value = '';
        document.getElementById('item-symbol').value = '';
        document.getElementById('item-logo').value = '';
        document.getElementById('item-description').value = '';
        document.getElementById('item-manual_price').value = '';
        document.getElementById('item-is_manual').checked = false;
        document.getElementById('item-is_active').checked = true;
        document.getElementById('item-sort_order').value = '0';

        showModal();
    }

    function editItem(item) {
        document.getElementById('formAction').value = 'edit';
        document.getElementById('modalTitle').innerText = 'ویرایش دارایی';
        document.getElementById('modalIcon').setAttribute('data-lucide', 'edit-2');
        document.getElementById('item-id').value = item.id;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-en_name').value = item.en_name;
        document.getElementById('item-symbol').value = item.symbol;
        document.getElementById('item-logo').value = item.logo;
        document.getElementById('item-current_logo').value = item.logo;
        document.getElementById('item-category').value = item.category;
        document.getElementById('item-description').value = item.description;
        document.getElementById('item-manual_price').value = item.manual_price;
        document.getElementById('item-is_manual').checked = item.is_manual == 1;
        document.getElementById('item-is_active').checked = item.is_active == 1;
        document.getElementById('item-sort_order').value = item.sort_order;

        showModal();
    }

    function showModal() {
        const modal = document.getElementById('itemModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.refreshIcons();
    }

    function closeModal() {
        const modal = document.getElementById('itemModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('itemModal');
        if (event.target == modal) closeModal();
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
