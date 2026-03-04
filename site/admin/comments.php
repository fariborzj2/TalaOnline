<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/comments.php';
check_permission("comments.view");

$message = '';
$error = '';

$comments_logic = new Comments($pdo);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    check_permission("comments.edit");
    $action = $_POST['action'];

    if ($action === 'delete') {
        check_permission("comments.delete");
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'نظر با موفقیت حذف شد.';
    } elseif ($action === 'update_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $message = 'وضعیت نظر بروزرسانی شد.';
    }
}

// Pagination & Filtering
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where_clauses = [];
$params = [];

if ($status_filter) {
    $where_clauses[] = "c.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_clauses[] = "(c.content LIKE ? OR u.name LIKE ? OR u.username LIKE ? OR c.guest_name LIKE ? OR c.guest_email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments c LEFT JOIN users u ON c.user_id = u.id $where_sql");
$count_stmt->execute($params);
$total_comments = $count_stmt->fetchColumn();
$total_pages = ceil($total_comments / $per_page);

// Fetch comments
$sql = "SELECT c.*, u.name as user_name, u.username as user_username, u.avatar as user_avatar
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        $where_sql
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

// Bind values manually to ensure proper types for LIMIT/OFFSET in some drivers
$i = 1;
foreach ($params as $p) {
    $stmt->bindValue($i++, $p);
}
$stmt->bindValue($i++, $per_page, PDO::PARAM_INT);
$stmt->bindValue($i++, $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll();

foreach ($comments as &$c) {
    $c['target_info'] = $comments_logic->getTargetInfo($c['target_id'], $c['target_type']);
}

$page_title = 'مدیریت نظرات';
$page_subtitle = 'تایید، ویرایش و مدیریت تمامی نظرات سایت';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="mb-6 animate-bounce-in">
        <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 flex items-center gap-3 text-emerald-700">
            <div class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="flex flex-col gap-6">
    <!-- Filters -->
    <div class="glass-card rounded-xl p-4 border border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative group">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="جستجو در نظرات..." class="text-xs !pr-10 !py-2 w-full md:w-64 border-slate-200 focus:border-indigo-500 transition-all">
                <i data-lucide="search" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>

            <select name="status" onchange="this.form.submit()" class="text-xs !py-2 border-slate-200 focus:border-indigo-500">
                <option value="">همه وضعیت‌ها</option>
                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>تایید شده</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>در انتظار تایید</option>
                <option value="spam" <?= $status_filter === 'spam' ? 'selected' : '' ?>>اسپم</option>
            </select>

            <?php if ($search || $status_filter): ?>
                <a href="comments.php" class="text-xs font-bold text-rose-500 hover:underline">پاکسازی فیلترها</a>
            <?php endif; ?>
        </form>

        <div class="flex items-center gap-2 bg-slate-50 px-3 py-2 rounded-lg border border-slate-100">
            <span class="text-[10px] font-bold text-slate-400">کل نظرات: <?= number_format($total_comments) ?></span>
        </div>
    </div>

    <!-- Table -->
    <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full admin-table">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>نوع نظر</th>
                        <th>مربوط به</th>
                        <th class="text-center">وضعیت</th>
                        <th>تاریخ</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($comments as $c): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 overflow-hidden shrink-0 border border-slate-100">
                                    <?php if ($c['user_avatar']): ?>
                                        <img src="../<?= htmlspecialchars($c['user_avatar']) ?>" alt="" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-300">
                                            <i data-lucide="user" class="w-5 h-5"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-black text-slate-900 text-xs"><?= htmlspecialchars($c['user_name'] ?: ($c['guest_name'] ?: 'ناشناس')) ?></p>
                                    <p class="text-[10px] font-bold text-slate-400">
                                        <?php if ($c['user_id']): ?>
                                            @<?= htmlspecialchars($c['user_username']) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($c['guest_email'] ?: 'مهمان') ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($c['parent_id']): ?>
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-indigo-50 text-indigo-600 text-[10px] font-black border border-indigo-100/50">
                                    <i data-lucide="corner-down-right" class="w-3 h-3"></i>
                                    پاسخ به #<?= $c['parent_id'] ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-black border border-slate-200/50">
                                    <i data-lucide="message-square" class="w-3 h-3"></i>
                                    نظر اصلی
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['target_info']): ?>
                                <a href="../<?= ltrim($c['target_info']['url'], '/') ?>" target="_blank" class="flex flex-col group/link">
                                    <span class="text-[10px] font-black text-slate-900 group-hover/link:text-indigo-600 transition-colors line-clamp-1"><?= htmlspecialchars($c['target_info']['title']) ?></span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase"><?= $c['target_type'] ?></span>
                                </a>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-rose-400 italic">هدف حذف شده</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php
                            $status_classes = [
                                'approved' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                'spam' => 'bg-rose-50 text-rose-600 border-rose-100'
                            ];
                            $status_labels = [
                                'approved' => 'تایید شده',
                                'pending' => 'در انتظار',
                                'spam' => 'اسپم'
                            ];
                            ?>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black border <?= $status_classes[$c['status']] ?? 'bg-slate-100 text-slate-500' ?>">
                                <?= $status_labels[$c['status']] ?? $c['status'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-[10px] font-bold text-slate-500"><?= jalali_time_tag($c['created_at']) ?></span>
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <?php if ($c['status'] !== 'approved'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-emerald-500 hover:bg-emerald-50 hover:border-emerald-100 rounded-lg transition-all flex items-center justify-center group/btn" title="تایید نظر">
                                            <i data-lucide="check-circle" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <button onclick='viewComment(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)' class="w-8 h-8 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all flex items-center justify-center group/btn" title="مشاهده کامل">
                                    <i data-lucide="eye" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                </button>

                                <?php if ($c['status'] !== 'spam'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="status" value="spam">
                                        <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-amber-500 hover:bg-amber-50 hover:border-amber-100 rounded-lg transition-all flex items-center justify-center group/btn" title="علامت‌گذاری به عنوان اسپم">
                                            <i data-lucide="shield-alert" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" class="inline" onsubmit="handleDelete(event, this, 'نظر')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="w-8 h-8 bg-white border border-slate-100 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all flex items-center justify-center group/btn" title="حذف دائمی">
                                        <i data-lucide="trash-2" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($comments)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-20">
                                <div class="flex flex-col items-center gap-3 text-slate-400">
                                    <i data-lucide="message-square" class="w-12 h-12 stroke-1"></i>
                                    <p class="font-bold">هیچ نظری یافت نشد.</p>
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
            <span class="text-[10px] font-bold text-slate-400">نمایش <?= count($comments) ?> از <?= number_format($total_comments) ?> نظر</span>
            <div class="flex items-center gap-1.5">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-lg text-slate-400 hover:text-indigo-600 transition-all">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center <?= $i == $page ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white border border-slate-200 text-slate-400 hover:text-indigo-600' ?> rounded-lg font-black text-[11px] transition-all">
                            <?= $i ?>
                        </a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span class="text-slate-300 px-1">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-lg text-slate-400 hover:text-indigo-600 transition-all">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Modal -->
<div id="commentModal" class="hidden fixed inset-0 z-[1000] bg-slate-900/40 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-xl p-6 md:p-8 transform transition-all animate-modal-up modal-container">
        <div class="flex items-center justify-between border-b border-slate-50 pb-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                    <i data-lucide="message-circle" class="w-7 h-7"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900">جزئیات نظر</h2>
                    <p class="text-[10px] text-slate-400 font-bold mt-1" id="modal-date"></p>
                </div>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 bg-slate-50 text-slate-400 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="space-y-6">
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                <div class="flex items-center gap-3">
                    <img id="modal-avatar" src="" alt="" class="w-12 h-12 rounded-full border-2 border-white shadow-sm">
                    <div>
                        <p class="font-black text-slate-900" id="modal-name"></p>
                        <p class="text-[10px] font-bold text-slate-400" id="modal-username"></p>
                    </div>
                </div>
                <div id="modal-target" class="text-left">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">بخش مربوطه</p>
                    <a id="modal-target-link" href="#" target="_blank" class="text-xs font-black text-indigo-600 hover:underline"></a>
                </div>
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block">متن نظر</label>
                <div class="bg-slate-50 p-6 rounded-xl border border-slate-100 text-slate-700 font-bold leading-relaxed whitespace-pre-wrap min-h-[120px]" id="modal-content"></div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-50">
                <button type="button" class="btn-v3 btn-v3-outline" onclick="closeModal()">بستن</button>
                <div id="modal-actions" class="flex items-center gap-2">
                    <!-- Actions will be injected via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function handleDelete(event, form, name) {
        event.preventDefault();
        const confirmed = await showConfirm(`آیا از حذف این ${name} اطمینان دارید؟`);
        if (confirmed) {
            form.submit();
        }
    }

    function viewComment(c) {
        document.getElementById('modal-name').innerText = c.user_name || (c.guest_name || 'ناشناس');
        document.getElementById('modal-username').innerText = c.user_id ? '@' + c.user_username : (c.guest_email || 'مهمان');
        document.getElementById('modal-avatar').src = c.user_avatar ? '../' + c.user_avatar : '../assets/images/default-avatar.png';
        document.getElementById('modal-content').innerText = c.content;
        document.getElementById('modal-date').innerText = c.created_at; // You might want to format this

        const targetLink = document.getElementById('modal-target-link');
        if (c.target_info) {
            targetLink.innerText = c.target_info.title;
            targetLink.href = '../' + c.target_info.url.replace(/^\//, '');
            targetLink.parentElement.classList.remove('hidden');
        } else {
            targetLink.parentElement.classList.add('hidden');
        }

        const actionsContainer = document.getElementById('modal-actions');
        actionsContainer.innerHTML = '';

        if (c.status !== 'approved') {
            actionsContainer.innerHTML += `
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="${c.id}">
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn-v3 btn-v3-primary !bg-emerald-600">تایید نظر</button>
                </form>
            `;
        }

        if (c.status !== 'spam') {
            actionsContainer.innerHTML += `
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="${c.id}">
                    <input type="hidden" name="status" value="spam">
                    <button type="submit" class="btn-v3 btn-v3-outline text-amber-600 border-amber-200 hover:bg-amber-50">اسپم</button>
                </form>
            `;
        }

        showModal();
    }

    function showModal() {
        const modal = document.getElementById('commentModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.refreshIcons();
    }

    function closeModal() {
        const modal = document.getElementById('commentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
