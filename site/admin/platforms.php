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

$page_title = 'مدیریت پلتفرم‌ها';
$page_subtitle = 'مدیریت لیست صرافی‌ها و پلتفرم‌های خرید طلا';

include __DIR__ . '/layout/header.php';
?>

<?php if ($message): ?>
    <div class="badge badge-success" style="padding: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i data-lucide="check-circle" style="width: 18px;"></i>
        <?= $message ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>لوگو</th>
                    <th>نام پلتفرم</th>
                    <th>قیمت (خرید / فروش)</th>
                    <th>کارمزد</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($platforms as $p): ?>
                <tr>
                    <td>
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: #f8fafc; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img src="../<?= htmlspecialchars($p['logo']) ?>" style="max-width: 32px; height: auto;">
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($p['name']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($p['en_name']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #16a34a;"><?= number_format((float)$p['buy_price']) ?></div>
                        <div style="font-weight: 600; color: #dc2626;"><?= number_format((float)$p['sell_price']) ?></div>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['fee']) ?>%</span></td>
                    <td>
                        <?php
                        $status_class = $p['status'] === 'مناسب خرید' ? 'badge-success' : 'badge-danger';
                        ?>
                        <span class="badge <?= $status_class ?>"><?= htmlspecialchars($p['status']) ?></span>
                    </td>
                    <td>
                        <button class="btn btn-outline" style="padding: 0.5rem;" onclick='editPlatform(<?= json_encode($p) ?>)'>
                            <i data-lucide="edit-3" style="width: 16px;"></i>
                            ویرایش
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="margin:0;">ویرایش پلتفرم</h2>
            <button onclick="closeModal()" style="color: var(--text-muted); cursor: pointer; background:none; border:none;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>نام پلتفرم</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label>نام انگلیسی</label>
                    <input type="text" name="en_name" id="edit-en_name">
                </div>
            </div>

            <div class="form-group">
                <label>آدرس لوگو</label>
                <input type="text" name="logo" id="edit-logo">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>قیمت خرید</label>
                    <input type="text" name="buy_price" id="edit-buy_price">
                </div>
                <div class="form-group">
                    <label>قیمت فروش</label>
                    <input type="text" name="sell_price" id="edit-sell_price">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>کارمزد (%)</label>
                    <input type="text" name="fee" id="edit-fee">
                </div>
                <div class="form-group">
                    <label>وضعیت (متن)</label>
                    <input type="text" name="status" id="edit-status">
                </div>
            </div>

            <div class="form-group">
                <label>لینک سایت</label>
                <input type="text" name="link" id="edit-link">
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex-grow: 1; justify-content: center;">ذخیره تغییرات</button>
                <button type="button" class="btn btn-outline" style="flex-grow: 1; justify-content: center;" onclick="closeModal()">انصراف</button>
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
        document.getElementById('editModal').style.display = 'flex';
        lucide.createIcons();
    }
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
