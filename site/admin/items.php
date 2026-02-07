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

$page_title = 'مدیریت آیتم‌ها';
$page_subtitle = 'مدیریت قیمت‌های دستی و اطلاعات دارایی‌ها';

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
                    <th>تصویر</th>
                    <th>نام آیتم</th>
                    <th>نماد API</th>
                    <th>قیمت API</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img src="../<?= htmlspecialchars($item['logo']) ?>" style="max-width: 24px; height: auto;">
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($item['name']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($item['en_name']) ?></div>
                    </td>
                    <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px;"><?= htmlspecialchars($item['symbol']) ?></code></td>
                    <td>
                        <?php if ($item['is_manual']): ?>
                            <div style="text-decoration: line-through; color: var(--text-muted); font-size: 0.8rem;"><?= number_format((float)$item['api_price']) ?></div>
                            <div style="color: var(--primary); font-weight: 700;"><?= number_format((float)$item['manual_price']) ?></div>
                        <?php else: ?>
                            <div style="font-weight: 700;"><?= number_format((float)$item['api_price']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['is_manual']): ?>
                            <span class="badge badge-warning">دستی (Override)</span>
                        <?php else: ?>
                            <span class="badge badge-success">خودکار (API)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-outline" style="padding: 0.5rem;" onclick='editItem(<?= json_encode($item) ?>)'>
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
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="margin:0;">ویرایش آیتم</h2>
            <button onclick="closeModal()" style="color: var(--text-muted); cursor: pointer; background:none; border:none;"><i data-lucide="x"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>نام فارسی</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label>نام انگلیسی</label>
                    <input type="text" name="en_name" id="edit-en_name">
                </div>
            </div>
            <div class="form-group">
                <label>آدرس لوگو (نسبت به پوشه site)</label>
                <input type="text" name="logo" id="edit-logo">
            </div>
            <div class="form-group">
                <label>توضیح کوتاه</label>
                <textarea name="description" id="edit-description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>قیمت دستی (تومان)</label>
                <input type="text" name="manual_price" id="edit-manual_price" placeholder="مثلاً 19500000">
            </div>
            <div class="form-group" style="background: #f8fafc; padding: 1rem; border-radius: 12px; display: flex; align-items: center; gap: 0.75rem;">
                <input type="checkbox" name="is_manual" id="edit-is_manual" style="width: 20px; height: 20px; accent-color: var(--primary);">
                <label for="edit-is_manual" style="margin-bottom: 0; cursor: pointer;">استفاده از قیمت دستی به جای API</label>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex-grow: 1; justify-content: center;">ذخیره تغییرات</button>
                <button type="button" class="btn btn-outline" style="flex-grow: 1; justify-content: center;" onclick="closeModal()">انصراف</button>
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
        document.getElementById('editModal').style.display = 'flex';
        lucide.createIcons();
    }
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == document.getElementById('editModal')) closeModal();
    }
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
