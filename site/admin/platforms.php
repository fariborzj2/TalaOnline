<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_login();

// Schema Self-Healing
try {
    $pdo->query("SELECT is_active FROM platforms LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE platforms ADD COLUMN is_active TINYINT(1) DEFAULT 1");
}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $en_name = $_POST['en_name'] ?? '';
        $buy_price = $_POST['buy_price'] ?? '';
        $sell_price = $_POST['sell_price'] ?? '';
        $fee = $_POST['fee'] ?? '';
        $status = $_POST['status'] ?? '';
        $link = $_POST['link'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
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
                $stmt = $pdo->prepare("INSERT INTO platforms (name, en_name, logo, buy_price, sell_price, fee, status, link, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $en_name, $logo, $buy_price, $sell_price, $fee, $status, $link, $is_active, $sort_order]);
                $message = 'پلتفرم جدید با موفقیت اضافه شد.';
            } catch (Exception $e) {
                $error = 'خطا در افزودن پلتفرم: ' . $e->getMessage();
            }
        } else {
            $stmt = $pdo->prepare("UPDATE platforms SET name = ?, en_name = ?, logo = ?, buy_price = ?, sell_price = ?, fee = ?, status = ?, link = ?, is_active = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$name, $en_name, $logo, $buy_price, $sell_price, $fee, $status, $link, $is_active, $sort_order, $id]);
            $message = 'پلتفرم با موفقیت بروزرسانی شد.';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM platforms WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'پلتفرم با موفقیت حذف شد.';
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $active_status = $_POST['is_active'];
        $stmt = $pdo->prepare("UPDATE platforms SET is_active = ? WHERE id = ?");
        $stmt->execute([$active_status, $id]);
        $message = 'وضعیت با موفقیت تغییر کرد.';
    } elseif ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        foreach ($ids as $index => $id) {
            $stmt = $pdo->prepare("UPDATE platforms SET sort_order = ? WHERE id = ?");
            $stmt->execute([$index, $id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

$platforms = $pdo->query("SELECT * FROM platforms ORDER BY sort_order ASC")->fetchAll();

$page_title = 'پلتفرم‌های تبادل';
$page_subtitle = 'مدیریت کامل صرافی‌ها، نرخ‌های خرید و فروش و کارمزدها';

$header_action = '<button onclick="openAddModal()" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن پلتفرم جدید</button>';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="mb-8">
        <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 flex items-center gap-3 text-emerald-700">
            <div class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-8">
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
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">لیست پلتفرم‌ها</h2>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative group w-full md:w-auto">
                <input type="text" id="tableSearch" placeholder="جستجو در پلتفرم‌ها..." class="text-xs !pr-12 !py-2 w-full md:w-64 border-slate-200 focus:border-indigo-500 transition-all">
                <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <select id="tableSort" class="text-xs !py-2 border-slate-200 focus:border-indigo-500 w-full md:w-auto">
                <option value="sort_order">ترتیب نمایش</option>
                <option value="name">نام (الفبا)</option>
                <option value="buy_desc">بیشترین نرخ خرید</option>
                <option value="fee_asc">کمترین کارمزد</option>
            </select>
            <span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-2 rounded-lg border border-slate-100">تعداد: <span id="itemCount"><?= count($platforms) ?></span></span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full admin-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th class="w-16">ترتیب</th>
                    <th class="w-20">لوگو</th>
                    <th>نام پلتفرم</th>
                    <th>نرخ خرید / فروش</th>
                    <th>کارمزد</th>
                    <th class="text-center">وضعیت</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($platforms as $p): ?>
                <tr class="hover:bg-slate-50/50 transition-colors group cursor-move" data-id="<?= $p['id'] ?>">
                    <td class="text-center text-slate-300">
                        <i data-lucide="grip-vertical" class="w-4 h-4 handle cursor-grab"></i>
                    </td>
                    <td class="text-center font-black text-slate-400 row-order"><?= $p['sort_order'] ?></td>
                    <td>
                        <div class="w-10 h-10 rounded-lg bg-slate-100 p-2 flex items-center justify-center">
                            <img src="../<?= htmlspecialchars($p['logo']) ?>" alt="" class="w-full h-full object-contain">
                        </div>
                    </td>
                    <td>
                        <p class="font-black text-slate-900"><?= htmlspecialchars($p['name']) ?></p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter"><?= htmlspecialchars($p['en_name']) ?></p>
                    </td>
                    <td>
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-black bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded">BUY</span>
                                <span class="text-xs font-black text-emerald-600 ltr-input"><?= number_format((float)$p['buy_price']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-black bg-rose-50 text-rose-600 px-1.5 py-0.5 rounded">SELL</span>
                                <span class="text-xs font-black text-rose-600 ltr-input"><?= number_format((float)$p['sell_price']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-indigo-50 text-indigo-600 border border-indigo-100 ltr-input">
                            <?= htmlspecialchars($p['fee']) ?>%
                        </span>
                    </td>
                    <td class="text-center">
                        <?php $active = $p['is_active'] ?? 1; ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="is_active" value="<?= $active ? 0 : 1 ?>">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black <?= $active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' ?>"></span>
                                <?= $active ? 'فعال' : 'غیرفعال' ?>
                            </button>
                        </form>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn" onclick='editPlatform(<?= json_encode($p) ?>)'>
                                <i data-lucide="edit-3" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('آیا از حذف این پلتفرم اطمینان دارید؟')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
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

<!-- Platform Modal -->
<div id="platformModal" class="hidden fixed inset-0 z-[1000] bg-slate-900/40 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-xl p-6 md:p-8 transform transition-all animate-modal-up modal-container">
        <div class="flex items-center justify-between border-b border-slate-50 pb-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                    <i data-lucide="layout" class="w-6 h-6" id="modalIcon"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900" id="modalTitle">افزودن پلتفرم جدید</h2>
                    <p class="text-[10px] text-slate-400 font-bold mt-1">تنظیمات نرخ‌ها، کارمزد و اطلاعات دسترسی</p>
                </div>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 bg-slate-50 text-slate-400 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="platform-id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label>نام فارسی پلتفرم</label>
                    <input type="text" name="name" id="platform-name" required>
                </div>
                <div class="form-group">
                    <label>نام انگلیسی (EN Name)</label>
                    <input type="text" name="en_name" id="platform-en_name" class="ltr-input">
                </div>
            </div>

            <div class="form-group mb-4">
                <label>لوگوی پلتفرم</label>
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
                        <input type="text" name="logo_url" id="platform-logo" class="ltr-input text-xs !py-2.5" placeholder="یا لینک تصویر...">
                    </div>
                </div>
                <input type="hidden" name="current_logo" id="platform-current_logo">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label>نرخ خرید (تومان)</label>
                    <input type="text" name="buy_price" id="platform-buy_price" class="text-emerald-600 font-black ltr-input">
                </div>
                <div class="form-group">
                    <label>نرخ فروش (تومان)</label>
                    <input type="text" name="sell_price" id="platform-sell_price" class="text-rose-600 font-black ltr-input">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="form-group">
                    <label>کارمزد (%)</label>
                    <input type="text" name="fee" id="platform-fee" class="font-black text-indigo-600 ltr-input">
                </div>
                <div class="form-group col-span-2">
                    <label>برچسب وضعیت (متن)</label>
                    <input type="text" name="status" id="platform-status" placeholder="مثلاً مناسب خرید">
                </div>
            </div>

            <div class="form-group mb-6">
                <label>لینک وب‌سایت پلتفرم</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="external-link" class="w-3.5 h-3.5"></i></span>
                    <input type="text" name="link" id="platform-link" class="ltr-input" placeholder="https://...">
                </div>
            </div>

            <div class="p-4 bg-slate-50 rounded-lg border border-slate-100 mb-6">
                <div class="flex items-center justify-between">
                    <h4 class="font-black text-slate-800 text-xs">تنظیمات سیستمی</h4>
                    <div class="flex gap-4">
                        <label class="relative inline-flex items-center cursor-pointer group">
                            <input type="checkbox" name="is_active" id="platform-is_active" class="sr-only peer" checked>
                            <div class="toggle-dot toggle-emerald"></div>
                            <span class="mr-2 text-[10px] font-black text-slate-600">فعال</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <label class="text-[10px] font-black text-slate-600">ترتیب:</label>
                            <input type="number" name="sort_order" id="platform-sort_order" value="0" class="w-16 py-1 px-2 text-xs">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-v3 btn-v3-primary flex-grow">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    ذخیره اطلاعات پلتفرم
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
    const tableBody = document.querySelector('.admin-table tbody');
    const originalRows = Array.from(tableBody.querySelectorAll('tr'));
    const itemCountSpan = document.getElementById('itemCount');

    function updateTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const sortBy = sortSelect.value;

        let filteredRows = originalRows.filter(row => {
            const text = row.innerText.toLowerCase();
            return text.includes(searchTerm);
        });

        // Sort
        filteredRows.sort((a, b) => {
            if (sortBy === 'name') {
                const nameA = a.querySelector('td:nth-child(3) p:first-child').innerText;
                const nameB = b.querySelector('td:nth-child(3) p:first-child').innerText;
                return nameA.localeCompare(nameB, 'fa');
            } else if (sortBy === 'buy_desc') {
                const priceA = parseFloat(a.querySelector('td:nth-child(4) .text-emerald-600').innerText.replace(/,/g, '')) || 0;
                const priceB = parseFloat(b.querySelector('td:nth-child(4) .text-emerald-600').innerText.replace(/,/g, '')) || 0;
                return priceB - priceA;
            } else if (sortBy === 'fee_asc') {
                const feeA = parseFloat(a.querySelector('td:nth-child(5) span').innerText) || 0;
                const feeB = parseFloat(b.querySelector('td:nth-child(5) span').innerText) || 0;
                return feeA - feeB;
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

            fetch('platforms.php', {
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

    function openAddModal() {
        document.getElementById('formAction').value = 'add';
        document.getElementById('modalTitle').innerText = 'افزودن پلتفرم جدید';
        document.getElementById('modalIcon').setAttribute('data-lucide', 'plus');
        document.getElementById('platform-id').value = '';
        document.getElementById('platform-name').value = '';
        document.getElementById('platform-en_name').value = '';
        document.getElementById('platform-logo').value = '';
        document.getElementById('platform-buy_price').value = '';
        document.getElementById('platform-sell_price').value = '';
        document.getElementById('platform-fee').value = '';
        document.getElementById('platform-status').value = '';
        document.getElementById('platform-link').value = '';
        document.getElementById('platform-is_active').checked = true;
        document.getElementById('platform-sort_order').value = '0';

        showModal();
    }

    function editPlatform(p) {
        document.getElementById('formAction').value = 'edit';
        document.getElementById('modalTitle').innerText = 'ویرایش پلتفرم';
        document.getElementById('modalIcon').setAttribute('data-lucide', 'edit-2');
        document.getElementById('platform-id').value = p.id;
        document.getElementById('platform-name').value = p.name;
        document.getElementById('platform-en_name').value = p.en_name;
        document.getElementById('platform-logo').value = p.logo;
        document.getElementById('platform-current_logo').value = p.logo;
        document.getElementById('platform-buy_price').value = p.buy_price;
        document.getElementById('platform-sell_price').value = p.sell_price;
        document.getElementById('platform-fee').value = p.fee;
        document.getElementById('platform-status').value = p.status;
        document.getElementById('platform-link').value = p.link;
        document.getElementById('platform-is_active').checked = p.is_active == 1;
        document.getElementById('platform-sort_order').value = p.sort_order;

        showModal();
    }

    function showModal() {
        const modal = document.getElementById('platformModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.refreshIcons();
    }

    function closeModal() {
        const modal = document.getElementById('platformModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('platformModal');
        if (event.target == modal) closeModal();
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
