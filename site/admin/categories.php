<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_login();

// Schema Self-Healing for Categories
try {
    $pdo->query("SELECT id FROM categories LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        en_name VARCHAR(100) DEFAULT NULL,
        icon VARCHAR(50) DEFAULT 'coins',
        description TEXT DEFAULT NULL,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// Check if new columns exist, if not add them (for existing tables)
try {
    $columns = $pdo->query("DESCRIBE categories")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('en_name', $columns)) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN en_name VARCHAR(100) DEFAULT NULL AFTER name");
    }
    if (!in_array('icon', $columns)) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN icon VARCHAR(50) DEFAULT 'coins' AFTER en_name");
    }
    if (!in_array('description', $columns)) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN description TEXT DEFAULT NULL AFTER icon");
    }
} catch (Exception $e) {}

// Populate initial categories
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO categories (slug, name, en_name, icon, sort_order) VALUES
            ('gold', 'طلا و جواهرات', 'gold market', 'coins', 1),
            ('coin', 'مسکوکات طلا', 'gold coins', 'circle-dollar-sign', 2),
            ('currency', 'ارزهای رایج', 'foreign currency', 'banknote', 3),
            ('crypto', 'ارزهای دیجیتال', 'cryptocurrency', 'bitcoin', 4)");
    }
} catch (Exception $e) {}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $en_name = $_POST['en_name'] ?? '';
        $icon = $_POST['icon'] ?? 'coins';
        $description = $_POST['description'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, en_name, icon, description, slug, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $en_name, $icon, $description, $slug, $sort_order]);
                $message = 'دسته‌بندی جدید با موفقیت اضافه شد.';
            } catch (Exception $e) {
                $error = 'خطا در افزودن دسته‌بندی: ' . $e->getMessage();
            }
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, en_name = ?, icon = ?, description = ?, slug = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$name, $en_name, $icon, $description, $slug, $sort_order, $id]);
                $message = 'دسته‌بندی با موفقیت بروزرسانی شد.';
            } catch (Exception $e) {
                $error = 'خطا در بروزرسانی دسته‌بندی: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'دسته‌بندی با موفقیت حذف شد.';
        } catch (Exception $e) {
            $error = 'خطا در حذف دسته‌بندی: ' . $e->getMessage();
        }
    } elseif ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        foreach ($ids as $index => $id) {
            $stmt = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
            $stmt->execute([$index, $id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();

$page_title = 'مدیریت دسته‌بندی‌ها';
$page_subtitle = 'مدیریت دسته‌بندی‌های ارز، طلا و سکه';

$header_action = '<button onclick="openAddModal()" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن دسته‌بندی جدید</button>';

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/editor.php';
?>

<script>
  initTinyMCE('#cat-description');
</script>

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
            <h2 class="text-lg font-black text-slate-800">لیست دسته‌بندی‌ها</h2>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative group w-full md:w-auto">
                <input type="text" id="tableSearch" placeholder="جستجو در دسته‌ها..." class="text-xs !pr-12 !py-2 w-full md:w-64 border-slate-200 focus:border-indigo-500 transition-all">
                <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-2 rounded-lg border border-slate-100">تعداد: <span id="itemCount"><?= count($categories) ?></span></span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full admin-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th class="w-16">ترتیب</th>
                    <th>نام دسته‌بندی</th>
                    <th>آیکون</th>
                    <th>نامک (Slug)</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($categories as $cat): ?>
                <tr class="hover:bg-slate-50/50 transition-colors group cursor-move" data-id="<?= $cat['id'] ?>">
                    <td class="text-center text-slate-300">
                        <i data-lucide="grip-vertical" class="w-4 h-4 handle cursor-grab"></i>
                    </td>
                    <td class="text-center font-black text-slate-400 row-order"><?= $cat['sort_order'] ?></td>
                    <td>
                        <p class="font-black text-slate-900"><?= htmlspecialchars($cat['name'] ?? '') ?></p>
                        <p class="text-[10px] text-slate-400 font-bold"><?= htmlspecialchars($cat['en_name'] ?? '') ?></p>
                    </td>
                    <td>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="<?= htmlspecialchars($cat['icon'] ?? 'coins') ?>" class="w-4 h-4"></i>
                            <span class="text-[10px] font-bold"><?= htmlspecialchars($cat['icon'] ?? 'coins') ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] font-black border border-indigo-100 ltr-input">
                            <?= htmlspecialchars($cat['slug']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn" onclick='editCategory(<?= json_encode($cat) ?>)'>
                                <i data-lucide="edit-3" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="handleDelete(event, this, 'دسته‌بندی')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
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

<!-- Category Modal -->
<div id="categoryModal" class="hidden fixed inset-0 z-[1000] bg-slate-900/40 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-xl p-6 md:p-8 transform transition-all animate-modal-up modal-container">
        <div class="flex items-center justify-between border-b border-slate-50 pb-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                    <i data-lucide="tag" class="w-6 h-6" id="modalIcon"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900" id="modalTitle">افزودن دسته‌بندی جدید</h2>
                    <p class="text-[10px] text-slate-400 font-bold mt-1">تنظیمات نام و آدرس دسته‌بندی</p>
                </div>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 bg-slate-50 text-slate-400 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="cat-id">

            <div class="form-group mb-4">
                <label>نام دسته‌بندی (فارسی)</label>
                <input type="text" name="name" id="cat-name" required placeholder="مثلاً بازار طلا و سکه">
            </div>

            <div class="form-group mb-4">
                <label>زیرعنوان (انگلیسی/توضیح کوتاه)</label>
                <input type="text" name="en_name" id="cat-en_name" placeholder="مثلاً gold market">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group mb-4">
                    <label>آیکون Lucide</label>
                    <input type="text" name="icon" id="cat-icon" placeholder="مثلاً coins" class="ltr-input">
                </div>
                <div class="form-group mb-4">
                    <label>نامک (Slug)</label>
                    <input type="text" name="slug" id="cat-slug" required class="ltr-input" placeholder="مثلاً gold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group mb-4">
                    <label>ترتیب نمایش</label>
                    <input type="number" name="sort_order" id="cat-sort_order" value="0">
                </div>
            </div>

            <div class="form-group mb-6">
                <label>محتوای متنی دسته‌بندی</label>
                <textarea name="description" id="cat-description"></textarea>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-v3 btn-v3-primary flex-grow">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    ذخیره اطلاعات
                </button>
                <button type="button" class="btn-v3 btn-v3-outline" onclick="closeModal()">انصراف</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Search logic
    const searchInput = document.getElementById('tableSearch');
    const tableBody = document.querySelector('.admin-table tbody');
    const originalRows = Array.from(tableBody.querySelectorAll('tr'));
    const itemCountSpan = document.getElementById('itemCount');

    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        let filteredCount = 0;

        originalRows.forEach(row => {
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

            fetch('categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    tableBody.querySelectorAll('tr').forEach((tr, index) => {
                        tr.querySelector('.row-order').innerText = index;
                    });
                }
            });
        }
    });

    async function handleDelete(event, form, name) {
        event.preventDefault();
        const confirmed = await showConfirm(`آیا از حذف ${name} اطمینان دارید؟ دارایی‌های این دسته ممکن است بدون دسته بمانند.`);
        if (confirmed) {
            form.submit();
        }
    }

    function openAddModal() {
        document.getElementById('formAction').value = 'add';
        document.getElementById('modalTitle').innerText = 'افزودن دسته‌بندی جدید';
        document.getElementById('modalIcon').setAttribute('data-lucide', 'plus');
        document.getElementById('cat-id').value = '';
        document.getElementById('cat-name').value = '';
        document.getElementById('cat-en_name').value = '';
        document.getElementById('cat-icon').value = 'coins';
        document.getElementById('cat-slug').value = '';
        document.getElementById('cat-sort_order').value = '0';

        if (tinymce.get('cat-description')) {
            tinymce.get('cat-description').setContent('');
        } else {
            document.getElementById('cat-description').value = '';
        }

        showModal();
    }

    function editCategory(cat) {
        document.getElementById('formAction').value = 'edit';
        document.getElementById('modalTitle').innerText = 'ویرایش دسته‌بندی';
        document.getElementById('modalIcon').setAttribute('data-lucide', 'edit-2');
        document.getElementById('cat-id').value = cat.id;
        document.getElementById('cat-name').value = cat.name;
        document.getElementById('cat-en_name').value = cat.en_name || '';
        document.getElementById('cat-icon').value = cat.icon || 'coins';
        document.getElementById('cat-slug').value = cat.slug;
        document.getElementById('cat-sort_order').value = cat.sort_order;

        if (tinymce.get('cat-description')) {
            tinymce.get('cat-description').setContent(cat.description || '');
        } else {
            document.getElementById('cat-description').value = cat.description || '';
        }

        showModal();
    }

    function showModal() {
        const modal = document.getElementById('categoryModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.refreshIcons();
    }

    function closeModal() {
        const modal = document.getElementById('categoryModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('categoryModal');
        if (event.target == modal) closeModal();
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
