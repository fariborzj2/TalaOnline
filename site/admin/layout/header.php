<?php require_once __DIR__ . '/../../../includes/db.php'; ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'مدیریت' ?> - طلا آنلاین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-modern.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main-wrap">
    <div class="page-header">
        <div class="page-title">
            <h1><?= $page_title ?? 'داشبورد' ?></h1>
            <p><?= $page_subtitle ?? 'خوش آمدید به پنل مدیریت طلا آنلاین' ?></p>
        </div>
        <div class="page-actions">
            <?php if(isset($header_action)) echo $header_action; ?>
        </div>
    </div>
