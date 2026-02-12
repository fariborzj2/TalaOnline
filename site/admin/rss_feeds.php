<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/rss_service.php';
check_login();

// Schema Self-Healing for RSS Feeds
try {
    $pdo->query("SELECT id FROM rss_feeds LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS rss_feeds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        url TEXT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $rssService = new RssService($pdo);

    if ($action === 'add') {
        $name = $_POST['name'] ?? '';
        $url = $_POST['url'] ?? '';
        if ($name && $url) {
            try {
                $stmt = $pdo->prepare("INSERT INTO rss_feeds (name, url) VALUES (?, ?)");
                $stmt->execute([$name, $url]);
                $rssService->clearCache();
                $message = 'فید جدید با موفقیت اضافه شد.';
            } catch (Exception $e) {
                $error = 'خطا در افزودن فید: ' . $e->getMessage();
            }
        } else {
            $error = 'لطفا تمامی فیلدها را پر کنید.';
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'] ?? '';
        $url = $_POST['url'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        try {
            $stmt = $pdo->prepare("UPDATE rss_feeds SET name = ?, url = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $url, $is_active, $id]);
            $rssService->clearCache();
            $message = 'تغییرات با موفقیت ذخیره شد.';
        } catch (Exception $e) {
            $error = 'خطا در ویرایش فید: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM rss_feeds WHERE id = ?");
            $stmt->execute([$id]);
            $rssService->clearCache();
            $message = 'فید با موفقیت حذف شد.';
        } catch (Exception $e) {
            $error = 'خطا در حذف فید: ' . $e->getMessage();
        }
    } elseif ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        foreach ($ids as $index => $id) {
            $stmt = $pdo->prepare("UPDATE rss_feeds SET sort_order = ? WHERE id = ?");
            $stmt->execute([$index, $id]);
        }
        $rssService->clearCache();
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'update_news_count') {
        $count = $_POST['news_count'] ?? 5;
        set_setting('news_count', $count);
        $rssService->clearCache();
        $message = 'تنظیمات با موفقیت بروزرسانی شد.';
    }
}

$feeds = $pdo->query("SELECT * FROM rss_feeds ORDER BY sort_order ASC")->fetchAll();
$news_count = get_setting('news_count', 5);

$page_title = 'مدیریت فیدهای خبری';
$page_subtitle = 'مدیریت منابع خبری RSS برای نمایش در سایدبار';

$header_action = '<button onclick="openAddModal()" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن فید جدید</button>';

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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
            <div class="px-8 py-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/30">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                        <i data-lucide="rss" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">لیست فیدها</h2>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full admin-table">
                    <thead>
                        <tr>
                            <th class="w-10"></th>
                            <th>نام منبع</th>
                            <th>آدرس فید</th>
                            <th class="text-center">وضعیت</th>
                            <th class="text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50" id="feedTableBody">
                        <?php foreach ($feeds as $feed): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group cursor-move" data-id="<?= $feed['id'] ?>" data-feed='<?= htmlspecialchars(json_encode($feed), ENT_QUOTES) ?>'>
                            <td class="text-center text-slate-300">
                                <i data-lucide="grip-vertical" class="w-4 h-4 handle cursor-grab"></i>
                            </td>
                            <td>
                                <p class="font-black text-slate-900"><?= htmlspecialchars($feed['name']) ?></p>
                            </td>
                            <td>
                                <span class="text-[10px] font-bold text-slate-400 ltr-input block truncate max-w-xs" title="<?= htmlspecialchars($feed['url']) ?>">
                                    <?= htmlspecialchars($feed['url']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($feed['is_active']): ?>
                                    <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded text-[10px] font-black border border-emerald-100">فعال</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-slate-50 text-slate-400 rounded text-[10px] font-black border border-slate-100">غیرفعال</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick='openEditModal(<?= json_encode($feed) ?>)' class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                        <i data-lucide="edit-3" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="handleDelete(event, this, 'فید')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $feed['id'] ?>">
                                        <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-rose-600 hover:border-rose-100 hover:bg-rose-50 rounded-lg transition-all flex items-center justify-center group/btn">
                                            <i data-lucide="trash-2" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($feeds)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-8 text-slate-400 font-bold">هیچ فیدی تعریف نشده است.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="glass-card rounded-xl p-6 border border-slate-200">
            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                <i data-lucide="settings-2" class="w-5 h-5 text-indigo-500"></i>
                تنظیمات نمایش
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_news_count">
                <div class="space-y-4">
                    <div>
                        <label>تعداد اخبار قابل نمایش</label>
                        <input type="number" name="news_count" value="<?= $news_count ?>" min="1" max="20" required>
                        <p class="text-[10px] text-slate-400 mt-2 font-bold leading-relaxed">تعداد اخباری که به صورت یکجا در سایدبار نمایش داده می‌شوند.</p>
                    </div>
                    <button type="submit" class="btn-v3 btn-v3-primary w-full">ذخیره تنظیمات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="feedModal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl pointer-events-auto animate-modal-up overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                <h3 id="modalTitle" class="text-xl font-black text-slate-800">افزودن فید جدید</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form method="POST" id="feedForm" class="p-8 space-y-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="feedId">

                <div class="space-y-4">
                    <div>
                        <label>نام منبع خبری</label>
                        <input type="text" name="name" id="feedName" placeholder="مثلا: خبرگزاری فارس" required>
                    </div>
                    <div>
                        <label>آدرس RSS Feed</label>
                        <input type="text" name="url" id="feedUrl" placeholder="https://example.com/rss" class="ltr-input" required>
                    </div>
                    <div id="activeToggle" class="hidden">
                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="font-black text-slate-700">وضعیت انتشار</span>
                            <div class="relative">
                                <input type="checkbox" name="is_active" id="feedActive" class="sr-only peer" value="1">
                                <div class="toggle-dot"></div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeModal()" class="btn-v3 btn-v3-outline flex-1">انصراف</button>
                    <button type="submit" class="btn-v3 btn-v3-primary flex-1">ذخیره اطلاعات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('feedModal');
    const form = document.getElementById('feedForm');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const feedId = document.getElementById('feedId');
    const feedName = document.getElementById('feedName');
    const feedUrl = document.getElementById('feedUrl');
    const feedActive = document.getElementById('feedActive');
    const activeToggle = document.getElementById('activeToggle');

    function openAddModal() {
        modalTitle.innerText = 'افزودن فید جدید';
        formAction.value = 'add';
        form.reset();
        activeToggle.classList.add('hidden');
        modal.classList.remove('hidden');
        lucide.createIcons();
    }

    function openEditModal(feed) {
        modalTitle.innerText = 'ویرایش فید منبع';
        formAction.value = 'edit';
        feedId.value = feed.id;
        feedName.value = feed.name;
        feedUrl.value = feed.url;
        feedActive.checked = feed.is_active == 1;
        activeToggle.classList.remove('hidden');
        modal.classList.remove('hidden');
        lucide.createIcons();
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // Initialize Sortable
    const tableBody = document.getElementById('feedTableBody');
    new Sortable(tableBody, {
        handle: '.handle',
        animation: 150,
        ghostClass: 'bg-indigo-50',
        onEnd: function() {
            const ids = Array.from(tableBody.querySelectorAll('tr')).map(tr => tr.dataset.id);
            const formData = new FormData();
            formData.append('action', 'reorder');
            ids.forEach(id => formData.append('ids[]', id));

            fetch('rss_feeds.php', {
                method: 'POST',
                body: formData
            });
        }
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
