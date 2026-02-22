<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/mail.php';
check_permission("users.view");

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Security violation: Invalid CSRF token.');
    }

    $target = $_POST['target'] ?? 'all';
    $role_id = $_POST['role_id'] ?? '';
    $verification_status = $_POST['verification_status'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';
    $individual_email = $_POST['individual_email'] ?? '';

    $query = "SELECT email, name FROM users WHERE 1=1";
    $params = [];

    if ($target === 'individual') {
        $query .= " AND email = ?";
        $params[] = $individual_email;
    } elseif ($target === 'by_role' && $role_id !== '') {
        $query .= " AND role_id = ?";
        $params[] = $role_id;
    } elseif ($target === 'by_status' && $verification_status !== '') {
        $query .= " AND is_verified = ?";
        $params[] = $verification_status;
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $recipients = $stmt->fetchAll();

        if (empty($recipients)) {
            $error = 'هیچ کاربری با معیارهای انتخاب شده یافت نشد.';
        } else {
            $count = 0;
            foreach ($recipients as $recipient) {
                $personalized_body = str_replace('{name}', $recipient['name'], $body);
                $personalized_subject = str_replace('{name}', $recipient['name'], $subject);

                // Convert newlines to <br> if the body doesn't look like full HTML
                if (strip_tags($personalized_body) === $personalized_body) {
                    $personalized_body = nl2br($personalized_body);
                }

                $wrapped_body = Mail::getProfessionalLayout($personalized_body);

                if (Mail::queueRaw($recipient['email'], $personalized_subject, $wrapped_body)) {
                    $count++;
                }
            }
            $message = "تعداد $count ایمیل در صف ارسال قرار گرفت. این ایمیل‌ها به زودی توسط سیستم در پس‌زمینه ارسال خواهند شد.";
        }
    } catch (Exception $e) {
        $error = 'خطا در ارسال ایمیل: ' . $e->getMessage();
    }
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY name ASC")->fetchAll();

$page_title = 'ارسال ایمیل';
$page_subtitle = 'ارسال اطلاع‌رسانی و ایمیل‌های گروهی به کاربران بر اساس نقش و وضعیت حساب';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="mb-8 animate-bounce-in">
        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center gap-3 text-emerald-700">
            <div class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-8 animate-bounce-in">
        <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 flex items-center gap-3 text-rose-700">
            <div class="w-8 h-8 bg-rose-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $error ?></span>
        </div>
    </div>
<?php endif; ?>

<form method="POST" class="max-w-4xl space-y-8 pb-10">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-50">
                <i data-lucide="send" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">تنظیمات گیرندگان</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Recipient Selection</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <div class="form-group">
                <label>ارسال به:</label>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-2">
                    <label class="relative flex items-center gap-3 p-4 border border-slate-100 rounded-xl cursor-pointer hover:bg-slate-50 transition-all">
                        <input type="radio" name="target" value="all" checked onchange="toggleTargetFields()" class="w-4 h-4 text-indigo-600">
                        <span class="font-bold text-sm text-slate-700">همه کاربران</span>
                    </label>
                    <label class="relative flex items-center gap-3 p-4 border border-slate-100 rounded-xl cursor-pointer hover:bg-slate-50 transition-all">
                        <input type="radio" name="target" value="by_role" onchange="toggleTargetFields()" class="w-4 h-4 text-indigo-600">
                        <span class="font-bold text-sm text-slate-700">بر اساس نقش</span>
                    </label>
                    <label class="relative flex items-center gap-3 p-4 border border-slate-100 rounded-xl cursor-pointer hover:bg-slate-50 transition-all">
                        <input type="radio" name="target" value="by_status" onchange="toggleTargetFields()" class="w-4 h-4 text-indigo-600">
                        <span class="font-bold text-sm text-slate-700">وضعیت تایید</span>
                    </label>
                    <label class="relative flex items-center gap-3 p-4 border border-slate-100 rounded-xl cursor-pointer hover:bg-slate-50 transition-all">
                        <input type="radio" name="target" value="individual" onchange="toggleTargetFields()" class="w-4 h-4 text-indigo-600">
                        <span class="font-bold text-sm text-slate-700">کاربر خاص</span>
                    </label>
                </div>
            </div>

            <div id="role_field" class="form-group hidden animate-fade-in">
                <label>انتخاب نقش</label>
                <select name="role_id">
                    <option value="">همه نقش‌ها</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="status_field" class="form-group hidden animate-fade-in">
                <label>وضعیت تایید حساب</label>
                <select name="verification_status">
                    <option value="1">تایید شده</option>
                    <option value="0">منتظر تایید</option>
                </select>
            </div>

            <div id="individual_field" class="form-group hidden animate-fade-in">
                <label>ایمیل کاربر گیرنده</label>
                <div class="input-icon-wrapper">
                    <span class="icon"><i data-lucide="mail" class="w-4 h-4"></i></span>
                    <input type="email" name="individual_email" placeholder="user@example.com" class="ltr-input">
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/30">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-600 border border-amber-50">
                <i data-lucide="edit-3" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-slate-800">محتوای ایمیل</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase ">Email Content</p>
            </div>
        </div>
        <div class="p-8 space-y-6">
            <div class="form-group">
                <label>موضوع ایمیل</label>
                <input type="text" name="subject" required placeholder="مثلاً: خبرنامه جدید سایت">
            </div>

            <div class="form-group">
                <label>متن ایمیل (HTML مجاز است)</label>
                <textarea name="body" rows="10" required placeholder="متن پیام خود را اینجا بنویسید..." class="font-mono text-sm"></textarea>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase ">می‌توانید از متغیر <code class="bg-slate-100 px-1 rounded">{name}</code> برای درج نام کاربر استفاده کنید.</p>
            </div>

            <div class="flex items-center justify-end gap-4 pt-4">
                <button type="submit" class="btn-v3 btn-v3-primary w-full md:w-auto min-w-[200px]">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    ارسال ایمیل به گیرندگان
                </button>
            </div>
        </div>
    </div>
</form>

<script>
function toggleTargetFields() {
    const target = document.querySelector('input[name="target"]:checked').value;
    document.getElementById('role_field').classList.add('hidden');
    document.getElementById('status_field').classList.add('hidden');
    document.getElementById('individual_field').classList.add('hidden');

    if (target === 'by_role') {
        document.getElementById('role_field').classList.remove('hidden');
    } else if (target === 'by_status') {
        document.getElementById('status_field').classList.remove('hidden');
    } else if (target === 'individual') {
        document.getElementById('individual_field').classList.remove('hidden');
    }
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
