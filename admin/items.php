<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
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

    // Simple logo handling (could be expanded to file upload)
    $logo = $_POST['logo'];

    $stmt = $pdo->prepare("UPDATE items SET name = ?, en_name = ?, description = ?, manual_price = ?, is_manual = ?, logo = ? WHERE id = ?");
    $stmt->execute([$name, $en_name, $description, $manual_price, $is_manual, $logo, $id]);
    $message = 'آیتم با موفقیت بروزرسانی شد.';
}

$items = $pdo->query("SELECT i.*, p.price as api_price FROM items i LEFT JOIN prices_cache p ON i.symbol = p.symbol ORDER BY i.sort_order ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت آیتم‌ها - طلا آنلاین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Shared CSS variables and base styles from index.php */
        :root { --primary: #e29b21; --bg: #f8fafc; --sidebar: #1e293b; --card: #ffffff; --text: #475569; --title: #1e293b; --border: #e2e8f0; }
        body { font-family: 'Vazirmatn', sans-serif; background-color: var(--bg); color: var(--text); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--sidebar); color: white; min-height: 100vh; padding: 30px 20px; box-sizing: border-box; position: fixed; right: 0; top: 0; }
        .main-content { flex-grow: 1; margin-right: 260px; padding: 40px; }
        .nav-menu { list-style: none; padding: 0; }
        .nav-link { color: #cbd5e1; text-decoration: none; display: block; padding: 12px 15px; border-radius: 12px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(226, 155, 33, 0.1); color: var(--primary); }
        .card { background: var(--card); border-radius: 20px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: right; padding: 15px; border-bottom: 1px solid var(--border); }
        .btn { padding: 8px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; }
        .btn-edit { background: #3b82f6; color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 20px; width: 500px; max-width: 90%; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; }
        .alert { padding: 15px; background: #dcfce7; color: #16a34a; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="font-size: 1.5rem; color: var(--primary); margin-bottom: 40px; text-align: center;">TalaOnline Admin</div>
    <ul class="nav-menu">
        <li class="nav-item"><a href="index.php" class="nav-link">داشبورد</a></li>
        <li class="nav-item"><a href="items.php" class="nav-link active">مدیریت آیتم‌ها</a></li>
        <li class="nav-item"><a href="platforms.php" class="nav-link">مدیریت پلتفرم‌ها</a></li>
        <li class="nav-item"><a href="settings.php" class="nav-link">تنظیمات سیستم</a></li>
    </ul>
</div>

<div class="main-content">
    <h1>مدیریت آیتم‌ها و قیمت‌ها</h1>

    <?php if ($message): ?>
        <div class="alert"><?= $message ?></div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>تصویر</th>
                    <th>نام آیتم</th>
                    <th>نماد API</th>
                    <th>قیمت API</th>
                    <th>قیمت دستی</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><img src="../<?= htmlspecialchars($item['logo']) ?>" width="30"></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><code><?= htmlspecialchars($item['symbol']) ?></code></td>
                    <td><?= number_format((float)$item['api_price']) ?></td>
                    <td><?= $item['manual_price'] ? number_format((float)$item['manual_price']) : '-' ?></td>
                    <td>
                        <?php if ($item['is_manual']): ?>
                            <span style="color: #e29b21; font-weight: bold;">دستی (Override)</span>
                        <?php else: ?>
                            <span style="color: #16a34a;">خودکار</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-edit" onclick="editItem(<?= htmlspecialchars(json_encode($item)) ?>)">ویرایش</button>
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
        <h2>ویرایش آیتم</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label>نام فارسی</label>
                <input type="text" name="name" id="edit-name" required>
            </div>
            <div class="form-group">
                <label>نام انگلیسی</label>
                <input type="text" name="en_name" id="edit-en_name">
            </div>
            <div class="form-group">
                <label>آدرس لوگو</label>
                <input type="text" name="logo" id="edit-logo">
            </div>
            <div class="form-group">
                <label>توضیح کوتاه</label>
                <textarea name="description" id="edit-description"></textarea>
            </div>
            <div class="form-group">
                <label>قیمت دستی (تومان)</label>
                <input type="text" name="manual_price" id="edit-manual_price" placeholder="مثلاً 19500000">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_manual" id="edit-is_manual"> استفاده از قیمت دستی به جای API
                </label>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-edit" style="flex-grow: 1;">ذخیره تغییرات</button>
                <button type="button" class="btn btn-outline" style="border: 1px solid #ddd;" onclick="closeModal()">انصراف</button>
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
        document.getElementById('editModal').style.display = 'block';
    }
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == document.getElementById('editModal')) closeModal();
    }
</script>

</body>
</html>
