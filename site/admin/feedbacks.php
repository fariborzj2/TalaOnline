<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_login();

// Schema Self-Healing
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedbacks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        email VARCHAR(255),
        subject VARCHAR(255),
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM feedbacks WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'پیام با موفقیت حذف شد.';
    } elseif ($action === 'toggle_read') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE feedbacks SET is_read = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $message = 'وضعیت پیام بروزرسانی شد.';
    }
}

$feedbacks_raw = $pdo->query("SELECT * FROM feedbacks ORDER BY created_at DESC")->fetchAll();
$feedbacks = [];
foreach ($feedbacks_raw as $row) {
    $row['formatted_date'] = jalali_time_tag($row['created_at'], 'time');
    $feedbacks[] = $row;
}

$page_title = 'پیام‌های کاربران';
$page_subtitle = 'مدیریت و مشاهده بازخوردهای ارسال شده از طرف کاربران';

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
                <i data-lucide="mail" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">لیست پیام‌ها</h2>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative group w-full md:w-auto">
                <input type="text" id="tableSearch" placeholder="جستجو در پیام‌ها..." class="text-xs !pr-12 !py-2 w-full md:w-64 border-slate-200 focus:border-indigo-500 transition-all">
                <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <span class="text-[10px] font-bold text-slate-400 bg-white px-3 py-2 rounded-lg border border-slate-100">تعداد: <span id="itemCount"><?= count($feedbacks) ?></span></span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full admin-table">
            <thead>
                <tr>
                    <th class="w-16">وضعیت</th>
                    <th>فرستنده</th>
                    <th>موضوع</th>
                    <th>تاریخ ارسال</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($feedbacks as $fb): ?>
                <tr class="hover:bg-slate-50/50 transition-colors group <?= !$fb['is_read'] ? 'bg-indigo-50/30' : '' ?>" data-id="<?= $fb['id'] ?>">
                    <td class="text-center">
                        <?php if (!$fb['is_read']): ?>
                            <span class="flex h-2 w-2 rounded-full bg-indigo-600 mx-auto" title="خوانده نشده"></span>
                        <?php else: ?>
                            <i data-lucide="check-check" class="w-4 h-4 text-slate-300 mx-auto" title="خوانده شده"></i>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex flex-col">
                            <span class="font-black text-slate-900"><?= htmlspecialchars($fb['name'] ?? 'ناشناس') ?></span>
                            <span class="text-[10px] font-bold text-slate-400"><?= htmlspecialchars($fb['email'] ?? 'بدون ایمیل') ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="font-bold text-slate-700"><?= htmlspecialchars($fb['subject'] ?? 'بدون موضوع') ?></span>
                    </td>
                    <td>
                        <span class="text-[10px] font-black text-slate-500"><?= jalali_time_tag($fb['created_at'], 'time') ?></span>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn" onclick='viewFeedback(<?= json_encode($fb) ?>)'>
                                <i data-lucide="eye" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="handleDelete(event, this, 'پیام')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $fb['id'] ?>">
                                <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-rose-600 hover:border-rose-100 hover:bg-rose-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                    <i data-lucide="trash-2" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($feedbacks)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-20 text-slate-400 font-bold">هیچ پیامی یافت نشد.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Modal -->
<div id="feedbackModal" class="hidden fixed inset-0 z-[1000] bg-slate-900/40 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-xl p-6 md:p-8 transform transition-all animate-modal-up modal-container">
        <div class="flex items-center justify-between border-b border-slate-50 pb-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                    <i data-lucide="mail-open" class="w-6 h-6"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900">جزئیات پیام</h2>
                    <p class="text-[10px] text-slate-400 font-bold mt-1" id="fb-date"></p>
                </div>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 bg-slate-50 text-slate-400 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label>فرستنده</label>
                    <p class="font-black text-slate-900 bg-slate-50 p-3 rounded-lg border border-slate-100" id="fb-name"></p>
                </div>
                <div>
                    <label>ایمیل</label>
                    <p class="font-bold text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 ltr-input" id="fb-email"></p>
                </div>
            </div>

            <div>
                <label>موضوع</label>
                <p class="font-black text-slate-900 bg-slate-50 p-3 rounded-lg border border-slate-100" id="fb-subject"></p>
            </div>

            <div>
                <label>متن پیام</label>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-slate-700 font-bold leading-relaxed whitespace-pre-wrap" id="fb-message"></div>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-slate-50">
                <form method="POST" class="flex-grow" id="toggleReadForm">
                    <input type="hidden" name="action" value="toggle_read">
                    <input type="hidden" name="id" id="fb-id">
                    <input type="hidden" name="status" id="fb-status">
                    <button type="submit" class="btn-v3 btn-v3-primary w-full" id="toggleReadBtn">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>علامت‌گذاری به عنوان خوانده شده</span>
                    </button>
                </form>
                <button type="button" class="btn-v3 btn-v3-outline" onclick="closeModal()">بستن</button>
            </div>
        </div>
    </div>
</div>

<script>
    const searchInput = document.getElementById('tableSearch');
    const tableBody = document.querySelector('.admin-table tbody');
    const originalRows = Array.from(tableBody.querySelectorAll('tr:not(.no-result)'));
    const itemCountSpan = document.getElementById('itemCount');

    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        originalRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        itemCountSpan.innerText = visibleCount;
    });

    async function handleDelete(event, form, name) {
        event.preventDefault();
        const confirmed = await showConfirm(`آیا از حذف این ${name} اطمینان دارید؟`);
        if (confirmed) {
            form.submit();
        }
    }

    // Helper to format date in JS if needed, but easier to pass it from PHP if possible.
    // For now, we will pass the formatted date in the JSON if we can, or just use the raw one.
    // Actually, let's just use the raw created_at in the modal for now, or pre-format it.

    function viewFeedback(fb) {
        document.getElementById('fb-id').value = fb.id;
        document.getElementById('fb-name').innerText = fb.name || 'ناشناس';
        document.getElementById('fb-email').innerText = fb.email || 'بدون ایمیل';
        document.getElementById('fb-subject').innerText = fb.subject || 'بدون موضوع';
        document.getElementById('fb-message').innerText = fb.message;

        // Use a hidden span or similar to get the formatted date from the row if possible,
        // or just accept raw for the modal for now.
        // Better: Pre-format in PHP.
        document.getElementById('fb-date').innerHTML = fb.formatted_date;

        const statusInput = document.getElementById('fb-status');
        const toggleReadBtn = document.getElementById('toggleReadBtn');

        if (fb.is_read == 1) {
            statusInput.value = 0;
            toggleReadBtn.innerHTML = '<i data-lucide="mail" class="w-4 h-4"></i> <span>علامت‌گذاری به عنوان خوانده نشده</span>';
        } else {
            statusInput.value = 1;
            toggleReadBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> <span>علامت‌گذاری به عنوان خوانده شده</span>';
        }

        showModal();
    }

    function showModal() {
        const modal = document.getElementById('feedbackModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.refreshIcons();
    }

    function closeModal() {
        const modal = document.getElementById('feedbackModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
