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

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت پلتفرم‌ها - طلا آنلاین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #e29b21; --bg: #f8fafc; --sidebar: #1e293b; --card: #ffffff; --text: #475569; --title: #1e293b; --border: #e2e8f0; }
        body { font-family: 'Vazirmatn', sans-serif; background-color: var(--bg); color: var(--text); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--sidebar); color: white; min-height: 100vh; padding: 30px 20px; box-sizing: border-box; position: fixed; right: 0; top: 0; }
        .main-content { flex-grow: 1; margin-right: 260px; padding: 40px; }
        .nav-link { color: #cbd5e1; text-decoration: none; display: block; padding: 12px 15px; border-radius: 12px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(226, 155, 33, 0.1); color: var(--primary); }
        .card { background: var(--card); border-radius: 20px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: right; padding: 15px; border-bottom: 1px solid var(--border); }
        .btn { padding: 8px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; }
        .btn-edit { background: #3b82f6; color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 2% auto; padding: 30px; border-radius: 20px; width: 600px; max-width: 90%; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; }
        .alert { padding: 15px; background: #dcfce7; color: #16a34a; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div style="font-size: 1.5rem; color: var(--primary); margin-bottom: 40px; text-align: center;">TalaOnline Admin</div>
    <ul class="nav-menu">
        <li class="nav-item"><a href="index.php" class="nav-link">داشبورد</a></li>
        <li class="nav-item"><a href="items.php" class="nav-link">مدیریت آیتم‌ها</a></li>
        <li class="nav-item"><a href="platforms.php" class="nav-link active">مدیریت پلتفرم‌ها</a></li>
        <li class="nav-item"><a href="settings.php" class="nav-link">تنظیمات سیستم</a></li>
    </ul>
</div>

<div class="main-content">
    <h1>مدیریت پلتفرم‌های طلا</h1>

    <?php if ($message): ?>
        <div class="alert"><?= $message ?></div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>لوگو</th>
                    <th>نام پلتفرم</th>
                    <th>قیمت خرید</th>
                    <th>قیمت فروش</th>
                    <th>کارمزد</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($platforms as $p): ?>
                <tr>
                    <td><img src="../<?= htmlspecialchars($p['logo']) ?>" width="30"></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= number_format((float)$p['buy_price']) ?></td>
                    <td><?= number_format((float)$p['sell_price']) ?></td>
                    <td><?= $p['fee'] ?>%</td>
                    <td><?= $p['status'] ?></td>
                    <td>
                        <button class="btn btn-edit" onclick="editPlatform(<?= htmlspecialchars(json_encode($p)) ?>)">ویرایش</button>
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
        <h2>ویرایش پلتفرم</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>قیمت خرید</label>
                    <input type="text" name="buy_price" id="edit-buy_price">
                </div>
                <div class="form-group">
                    <label>قیمت فروش</label>
                    <input type="text" name="sell_price" id="edit-sell_price">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-edit" style="flex-grow: 1;">ذخیره تغییرات</button>
                <button type="button" class="btn btn-outline" style="border: 1px solid #ddd;" onclick="closeModal()">انصراف</button>
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
        document.getElementById('editModal').style.display = 'block';
    }
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
</script>

</body>
</html>
