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
    $description = $_POST['description'];
    $manual_price = $_POST['manual_price'];
    $is_manual = isset($_POST['is_manual']) ? 1 : 0;
    $logo = $_POST['logo'];

    $stmt = $pdo->prepare("UPDATE items SET name = ?, en_name = ?, description = ?, manual_price = ?, is_manual = ?, logo = ? WHERE id = ?");
    $stmt->execute([$name, $en_name, $description, $manual_price, $is_manual, $logo, $id]);
    $message = 'آیتم با موفقیت بروزرسانی شد.';
}

$items = $pdo->query("SELECT i.*, p.price as api_price FROM items i LEFT JOIN prices_cache p ON i.symbol = p.symbol ORDER BY i.sort_order ASC")->fetchAll();

$page_title = 'مدیریت دارایی‌ها';
$page_subtitle = 'مدیریت قیمت‌های دستی، توضیحات و لوگوی دارایی‌های نمایش داده شده در سایت';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="mb-6 animate-bounce-in">
        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex items-center gap-3 text-emerald-700 shadow-sm">
            <div class="w-8 h-8 bg-emerald-500 text-white rounded-lg flex items-center justify-center shadow-lg shadow-emerald-200">
                <i data-lucide="check" class="w-5 h-5"></i>
            </div>
            <span class="font-bold"><?= $message ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="glass-card rounded-3xl overflow-hidden shadow-xl shadow-slate-200/50 border border-slate-100">
    <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-400 shadow-sm">
                <i data-lucide="list" class="w-5 h-5"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">لیست دارایی‌ها</h2>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 ml-2">تعداد کل: <?= count($items) ?></span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full admin-table">
            <thead>
                <tr>
                    <th class="w-20">لوگو</th>
                    <th>نام و عنوان</th>
                    <th>نماد API</th>
                    <th>قیمت نمایشی</th>
                    <th>منبع قیمت</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-slate-50/50 transition-colors group">
                    <td>
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 p-2.5 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <img src="../<?= htmlspecialchars($item['logo']) ?>" alt="" class="w-full h-full object-contain">
                        </div>
                    </td>
                    <td>
                        <p class="font-black text-slate-900"><?= htmlspecialchars($item['name']) ?></p>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-tighter"><?= htmlspecialchars($item['en_name']) ?></p>
                    </td>
                    <td>
                        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-black border border-slate-200">
                            <?= htmlspecialchars($item['symbol']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($item['is_manual']): ?>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-slate-400 line-through font-bold"><?= number_format((float)$item['api_price']) ?></span>
                                <span class="text-base font-black text-indigo-600"><?= number_format((float)$item['manual_price']) ?> <small class="text-[10px] text-slate-400">تومان</small></span>
                            </div>
                        <?php else: ?>
                            <span class="text-base font-black text-slate-900"><?= number_format((float)$item['api_price']) ?> <small class="text-[10px] text-slate-400">تومان</small></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['is_manual']): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-amber-50 text-amber-600 border border-amber-100">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                قیمت دستی
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-600 border border-emerald-100">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                وب‌سرویس (API)
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button class="w-10 h-10 bg-white border border-slate-100 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50 rounded-xl transition-all shadow-sm flex items-center justify-center mx-auto group/btn" onclick='editItem(<?= json_encode($item) ?>)'>
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
<div id="editModal" class="hidden fixed inset-0 z-[1000] bg-slate-900/60 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl p-8 lg:p-10 transform transition-all animate-modal-up">
        <div class="flex items-center justify-between border-b border-slate-50 pb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                    <i data-lucide="edit-2" class="w-6 h-6"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900">ویرایش اطلاعات دارایی</h2>
                    <p class="text-xs text-slate-400 font-bold">تنظیمات قیمت و مشخصات ظاهری</p>
                </div>
            </div>
            <button onclick="closeModal()" class="w-10 h-10 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center hover:bg-slate-100 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" class="mt-8">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="form-group">
                    <label>نام فارسی دارایی</label>
                    <input type="text" name="name" id="edit-name" required placeholder="مثلاً طلا 18 عیار">
                </div>
                <div class="form-group">
                    <label>نام انگلیسی (EN Name)</label>
                    <input type="text" name="en_name" id="edit-en_name" placeholder="مثلاً Gold 18K">
                </div>
            </div>

            <div class="form-group mb-6">
                <label>مسیر لوگو (Logo Path)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                        <i data-lucide="image" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="logo" id="edit-logo" class="pr-10" placeholder="assets/img/coins/gold.png">
                </div>
                <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase tracking-wider pr-1">مسیر فایل باید نسبت به پوشه اصلی سایت باشد</p>
            </div>

            <div class="form-group mb-6">
                <label>توضیح کوتاه (برای سئو و نمایش)</label>
                <textarea name="description" id="edit-description" rows="3" placeholder="توضیحات کوتاهی در مورد این دارایی بنویسید..."></textarea>
            </div>

            <div class="p-6 bg-slate-50 rounded-3xl border-2 border-slate-100 mb-8 transition-all hover:border-indigo-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center text-amber-500 shadow-sm">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </div>
                        <h4 class="font-black text-slate-800">تنظیمات اورراید قیمت</h4>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_manual" id="edit-is_manual" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:-translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="mr-3 text-xs font-black text-slate-600">فعالسازی قیمت دستی</span>
                    </label>
                </div>
                <div class="form-group mb-0">
                    <label class="text-xs text-slate-400">قیمت دستی (تومان)</label>
                    <input type="text" name="manual_price" id="edit-manual_price" placeholder="قیمت را بدون جداکننده وارد کنید">
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="btn-v3 btn-v3-primary flex-grow">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    ذخیره تغییرات نهایی
                </button>
                <button type="button" class="btn-v3 btn-v3-outline" onclick="closeModal()">انصراف</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editItem(item) {
        document.getElementById('edit-id').value = item.id;
        document.getElementById('edit-name').value = item.name;
        document.getElementById('edit-en_name').value = item.en_name;
        document.getElementById('edit-logo').value = item.logo;
        document.getElementById('edit-description').value = item.description;
        document.getElementById('edit-manual_price').value = item.manual_price;
        document.getElementById('edit-is_manual').checked = item.is_manual == 1;

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

    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) closeModal();
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
