<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_permission("settings.view");

$message = '';
if (isset($_POST['action']) && $_POST['action'] === 'save') {
    $id = $_POST['id'] ?? null;
    $slug = $_POST['slug'];
    $title = $_POST['title'];
    $body = $_POST['body'];
    $action_url = $_POST['action_url'];
    $channels = implode(',', $_POST['channels'] ?? []);
    $priority = $_POST['priority'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE notification_templates SET slug=?, title=?, body=?, action_url=?, channels=?, priority=? WHERE id=?");
        $stmt->execute([$slug, $title, $body, $action_url, $channels, $priority, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO notification_templates (slug, title, body, action_url, channels, priority) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$slug, $title, $body, $action_url, $channels, $priority]);
    }
    $message = 'قالب با موفقیت ذخیره شد.';
}

$templates = $pdo->query("SELECT * FROM notification_templates ORDER BY id DESC")->fetchAll();

$page_title = 'مدیریت قالب‌های اعلان';
include __DIR__ . '/layout/header.php';
?>

<div class="glass-card rounded-xl overflow-hidden border border-slate-200">
    <table class="w-full admin-table">
        <thead>
            <tr>
                <th>نامک (Slug)</th>
                <th>عنوان</th>
                <th>کانال‌ها</th>
                <th>اولویت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            <?php foreach ($templates as $t): ?>
            <tr class="hover:bg-slate-50/50">
                <td class="font-mono text-xs"><?= htmlspecialchars($t['slug']) ?></td>
                <td class="font-bold"><?= htmlspecialchars($t['title']) ?></td>
                <td>
                    <?php foreach (explode(',', $t['channels']) as $c): ?>
                        <span class="px-2 py-0.5 bg-slate-100 rounded text-[9px] font-black"><?= $c ?></span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <span class="px-2 py-0.5 rounded text-[9px] font-black <?= $t['priority'] === 'high' ? 'bg-rose-100 text-rose-600' : ($t['priority'] === 'medium' ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-600') ?>">
                        <?= $t['priority'] ?>
                    </span>
                </td>
                <td>
                    <button onclick='editTemplate(<?= json_encode($t) ?>)' class="btn-v3 btn-v3-outline !py-1 text-[10px]">ویرایش</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="templateModal" class="hidden fixed inset-0 z-[1000] bg-slate-900/40 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-xl p-8 transform transition-all modal-container">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-black text-slate-900" id="modalTitle">افزودن قالب جدید</h2>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x"></i></button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="tpl_id">

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="text-xs font-black">نامک (Slug)</label>
                    <input type="text" name="slug" id="tpl_slug" required class="font-mono text-xs">
                </div>
                <div class="form-group">
                    <label class="text-xs font-black">اولویت</label>
                    <select name="priority" id="tpl_priority">
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="text-xs font-black">عنوان پیام</label>
                <input type="text" name="title" id="tpl_title" required>
            </div>

            <div class="form-group">
                <label class="text-xs font-black">متن پیام</label>
                <textarea name="body" id="tpl_body" rows="3" required></textarea>
                <p class="text-[9px] text-slate-400 mt-1">از متغیرهایی مثل {name}, {symbol}, {change} استفاده کنید.</p>
            </div>

            <div class="form-group">
                <label class="text-xs font-black">آدرس اکشن (URL)</label>
                <input type="text" name="action_url" id="tpl_url" class="ltr-input">
            </div>

            <div class="form-group">
                <label class="text-xs font-black">کانال‌های ارسال</label>
                <div class="flex gap-4 mt-2">
                    <label class="flex items-center gap-2 text-xs font-bold"><input type="checkbox" name="channels[]" value="webpush" id="chk_webpush"> Web Push</label>
                    <label class="flex items-center gap-2 text-xs font-bold"><input type="checkbox" name="channels[]" value="email" id="chk_email"> Email</label>
                    <label class="flex items-center gap-2 text-xs font-bold"><input type="checkbox" name="channels[]" value="app" id="chk_app"> In-App</label>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="btn-v3 btn-v3-outline">انصراف</button>
                <button type="submit" class="btn-v3 btn-v3-primary px-8">ذخیره قالب</button>
            </div>
        </form>
    </div>
</div>

<div class="mt-6">
    <button onclick="addTemplate()" class="btn-v3 btn-v3-primary"><i data-lucide="plus" class="w-4 h-4"></i> افزودن قالب جدید</button>
</div>

<script>
    function addTemplate() {
        document.getElementById('modalTitle').innerText = 'افزودن قالب جدید';
        document.getElementById('tpl_id').value = '';
        document.getElementById('tpl_slug').value = '';
        document.getElementById('tpl_title').value = '';
        document.getElementById('tpl_body').value = '';
        document.getElementById('tpl_url').value = '';
        document.getElementById('tpl_priority').value = 'medium';
        document.querySelectorAll('input[name="channels[]"]').forEach(c => c.checked = true);
        showModal();
    }

    function editTemplate(t) {
        document.getElementById('modalTitle').innerText = 'ویرایش قالب';
        document.getElementById('tpl_id').value = t.id;
        document.getElementById('tpl_slug').value = t.slug;
        document.getElementById('tpl_title').value = t.title;
        document.getElementById('tpl_body').value = t.body;
        document.getElementById('tpl_url').value = t.action_url;
        document.getElementById('tpl_priority').value = t.priority;

        const channels = t.channels.split(',');
        document.getElementById('chk_webpush').checked = channels.includes('webpush');
        document.getElementById('chk_email').checked = channels.includes('email');
        document.getElementById('chk_app').checked = channels.includes('app');

        showModal();
    }

    function showModal() {
        const modal = document.getElementById('templateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('templateModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
