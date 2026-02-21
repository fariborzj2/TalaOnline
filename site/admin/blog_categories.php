<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_permission("blog_categories.view");

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM blog_categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'دسته‌بندی وبلاگ با موفقیت حذف شد.';
        } catch (Exception $e) {
            $error = 'خطا در حذف دسته‌بندی: ' . $e->getMessage();
        }
    } elseif ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        foreach ($ids as $index => $id) {
            $stmt = $pdo->prepare("UPDATE blog_categories SET sort_order = ? WHERE id = ?");
            $stmt->execute([$index, $id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

$categories = $pdo->query("SELECT * FROM blog_categories ORDER BY sort_order ASC")->fetchAll();

$page_title = 'مدیریت دسته‌بندی‌های وبلاگ';
$page_subtitle = 'مدیریت موضوعات و دسته‌بندی‌های مقالات وبلاگ';

$header_action = '<a href="blog_category_edit.php" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن دسته‌بندی جدید</a>';

include __DIR__ . '/layout/header.php';
?>

<?php
if (isset($_GET['message']) && $_GET['message'] === 'success') {
    $message = 'عملیات با موفقیت انجام شد.';
}
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
                <i data-lucide="tags" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">لیست دسته‌بندی‌های وبلاگ</h2>
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
                        <p class="text-[10px] text-slate-400 font-bold truncate max-w-md"><?= htmlspecialchars($cat['description'] ?? '') ?></p>
                    </td>
                    <td>
                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] font-black border border-indigo-100 ltr-input">
                            <?= htmlspecialchars($cat['slug']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="blog_category_edit.php?id=<?= $cat['id'] ?>" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                <i data-lucide="edit-3" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                            </a>
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
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-10 text-slate-400 font-bold">هیچ دسته‌بندی یافت نشد.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Search logic
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

    // Initialize Sortable
    if (document.querySelectorAll('.admin-table tbody tr').length > 1) {
        new Sortable(tableBody, {
            handle: '.handle',
            animation: 150,
            ghostClass: 'bg-indigo-50',
            onEnd: function() {
                const ids = Array.from(tableBody.querySelectorAll('tr')).map(tr => tr.dataset.id);
                const formData = new FormData();
                formData.append('action', 'reorder');
                ids.forEach(id => formData.append('ids[]', id));

                fetch('blog_categories.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        tableBody.querySelectorAll('tr').forEach((tr, index) => {
                            const orderTd = tr.querySelector('.row-order');
                            if (orderTd) orderTd.innerText = index;
                        });
                    }
                });
            }
        });
    }

    async function handleDelete(event, form, name) {
        event.preventDefault();
        const confirmed = await showConfirm(`آیا از حذف ${name} اطمینان دارید؟ مقالات این دسته ممکن است بدون دسته بمانند.`);
        if (confirmed) {
            form.submit();
        }
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
