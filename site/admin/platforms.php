<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_login();

$message = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $en_name = $_POST['en_name'];
    $buy_price = $_POST['buy_price'];
    $sell_price = $_POST['sell_price'];
    $fee = $_POST['fee'];
    $status = $_POST['status'];
    $link = $_POST['link'];
    $logo = $_POST['logo'];

    $stmt = $pdo->prepare("UPDATE platforms SET name = ?, en_name = ?, buy_price = ?, sell_price = ?, fee = ?, status = ?, link = ?, logo = ? WHERE id = ?");
    $stmt->execute([$name, $en_name, $buy_price, $sell_price, $fee, $status, $link, $logo, $id]);
    $message = 'پلتفرم با موفقیت بروزرسانی شد.';
}

$platforms = $pdo->query("SELECT * FROM platforms ORDER BY sort_order ASC")->fetchAll();

$page_title = 'پلتفرم‌های تبادل';
$page_subtitle = 'مدیریت و مقایسه نرخ‌های خرید و فروش در صرافی‌ها و پلتفرم‌های طلا';

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

<div class="glass-card rounded-[20px] overflow-hidden border border-slate-200">
    <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-400">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">لیست پلتفرم‌ها</h2>
        </div>
        <div class="flex items-center gap-2">
             <span class="text-xs font-bold text-slate-400">تعداد: <?= count($platforms) ?></span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full admin-table">
            <thead>
                <tr>
                    <th class="w-20">لوگو</th>
                    <th>نام پلتفرم</th>
                    <th>نرخ خرید / فروش</th>
                    <th>کارمزد</th>
                    <th>وضعیت</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($platforms as $p): ?>
                <tr class="hover:bg-slate-50/50 transition-colors group">
                    <td>
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 p-2.5 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <img src="../<?= htmlspecialchars($p['logo']) ?>" alt="" class="w-full h-full object-contain">
                        </div>
                    </td>
                    <td>
                        <p class="font-black text-slate-900"><?= htmlspecialchars($p['name']) ?></p>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-tighter"><?= htmlspecialchars($p['en_name']) ?></p>
                    </td>
                    <td>
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded">BUY</span>
                                <span class="text-sm font-black text-emerald-600"><?= number_format((float)$p['buy_price']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black bg-rose-50 text-rose-600 px-1.5 py-0.5 rounded">SELL</span>
                                <span class="text-sm font-black text-rose-600"><?= number_format((float)$p['sell_price']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-indigo-50 text-indigo-600 border border-indigo-100">
                            <?= htmlspecialchars($p['fee']) ?>%
                        </span>
                    </td>
                    <td>
                        <?php
                        $is_good = $p['status'] === 'مناسب خرید';
                        $status_color = $is_good ? 'emerald' : 'rose';
                        ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-<?= $status_color ?>-50 text-<?= $status_color ?>-600 border border-<?= $status_color ?>-100">
                            <span class="w-1.5 h-1.5 bg-<?= $status_color ?>-500 rounded-full <?= $is_good ? 'animate-pulse' : '' ?>"></span>
                            <?= htmlspecialchars($p['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="w-10 h-10 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-xl transition-all flex items-center justify-center mx-auto group/btn" onclick='editPlatform(<?= json_encode($p) ?>)'>
                            <i data-lucide="edit-3" class="w-5 h-5 group-hover/btn:scale-110 transition-transform"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 z-[1000] bg-slate-900/40 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-[20px] p-5 md:p-6 transform transition-all animate-modal-up">
        <div class="flex items-center justify-between border-b border-slate-50 pb-5">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                    <i data-lucide="layout" class="w-6 h-6"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900">ویرایش پلتفرم</h2>
                    <p class="text-[10px] text-slate-400 font-bold mt-1">تنظیمات نرخ‌ها، کارمزد و اطلاعات دسترسی</p>
                </div>
            </div>
            <button onclick="closeModal()" class="w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="mt-6">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="form-group">
                    <label>نام فارسی پلتفرم</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label>نام انگلیسی (EN Name)</label>
                    <input type="text" name="en_name" id="edit-en_name">
                </div>
            </div>

            <div class="form-group mb-6">
                <label>مسیر لوگو (Logo Path)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                        <i data-lucide="image" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="logo" id="edit-logo" class="pr-10">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="form-group">
                    <label>نرخ خرید (تومان)</label>
                    <input type="text" name="buy_price" id="edit-buy_price" class="text-emerald-600 font-black">
                </div>
                <div class="form-group">
                    <label>نرخ فروش (تومان)</label>
                    <input type="text" name="sell_price" id="edit-sell_price" class="text-rose-600 font-black">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="form-group">
                    <label>کارمزد (%)</label>
                    <input type="text" name="fee" id="edit-fee" class="font-black text-indigo-600">
                </div>
                <div class="form-group">
                    <label>برچسب وضعیت</label>
                    <input type="text" name="status" id="edit-status" placeholder="مثلاً مناسب خرید">
                </div>
            </div>

            <div class="form-group mb-8">
                <label>لینک وب‌سایت پلتفرم</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="link" id="edit-link" class="pr-10" placeholder="https://...">
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-v3 btn-v3-primary flex-grow">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    ذخیره تغییرات پلتفرم
                </button>
                <button type="button" class="btn-v3 btn-v3-outline" onclick="closeModal()">انصراف</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editPlatform(p) {
        document.getElementById('edit-id').value = p.id;
        document.getElementById('edit-name').value = p.name;
        document.getElementById('edit-en_name').value = p.en_name;
        document.getElementById('edit-logo').value = p.logo;
        document.getElementById('edit-buy_price').value = p.buy_price;
        document.getElementById('edit-sell_price').value = p.sell_price;
        document.getElementById('edit-fee').value = p.fee;
        document.getElementById('edit-status').value = p.status;
        document.getElementById('edit-link').value = p.link;

        const modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        window.refreshIcons();
    }

    function closeModal() {
        const modal = document.getElementById('editModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
