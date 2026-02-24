<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
if (isset($_GET["id"])) { check_permission("assets.edit"); } else { check_permission("assets.create"); }

// Schema Self-Healing
if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS item_faqs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            sort_order INT DEFAULT 0
        )");
    } catch (Exception $e) {}
}

$id = $_GET['id'] ?? null;
$item = null;
$faqs = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item) {
        $stmt = $pdo->prepare("SELECT * FROM item_faqs WHERE item_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$id]);
        $faqs = $stmt->fetchAll();
    }
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $name = $_POST['name'] ?? '';
        $en_name = $_POST['en_name'] ?? '';
        $symbol = $_POST['symbol'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $category = $_POST['category'] ?? 'gold';
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $description = $_POST['description'] ?? '';
        $long_description = $_POST['long_description'] ?? '';
        $h1_title = $_POST['h1_title'] ?? '';
        $page_title = $_POST['page_title'] ?? '';
        $meta_description = $_POST['meta_description'] ?? '';
        $meta_keywords = $_POST['meta_keywords'] ?? '';
        $manual_price = $_POST['manual_price'] ?? '';
        $is_manual = isset($_POST['is_manual']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $show_in_summary = isset($_POST['show_in_summary']) ? 1 : 0;
        $show_chart = isset($_POST['show_chart']) ? 1 : 0;
        $related_item_symbol = $_POST['related_item_symbol'] ?? null;

        // Sanitize updated_at to prevent "zero dates"
        $updated_at = $_POST['updated_at'] ?? '';
        if (empty($updated_at) || strpos($updated_at, '0000') === 0) {
            $updated_at = date('Y-m-d H:i:s');
        }

        // Handle Image Upload
        $logo = $_POST['current_logo'] ?? '';
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_upload($_FILES['logo_file']);
            if ($uploaded_path) {
                $logo = $uploaded_path;
            }
        } elseif (!empty($_POST['logo_url'])) {
            $logo = $_POST['logo_url'];
        }

        try {
            $pdo->beginTransaction();

            if ($id) {
                $stmt = $pdo->prepare("UPDATE items SET name = ?, en_name = ?, symbol = ?, slug = ?, category = ?, sort_order = ?, description = ?, long_description = ?, h1_title = ?, page_title = ?, meta_description = ?, meta_keywords = ?, manual_price = ?, is_manual = ?, is_active = ?, show_in_summary = ?, show_chart = ?, logo = ?, related_item_symbol = ?, updated_at = ? WHERE id = ?");
                $stmt->execute([$name, $en_name, $symbol, $slug, $category, $sort_order, $description, $long_description, $h1_title, $page_title, $meta_description, $meta_keywords, $manual_price, $is_manual, $is_active, $show_in_summary, $show_chart, $logo, $related_item_symbol, $updated_at, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO items (name, en_name, symbol, slug, category, sort_order, description, long_description, h1_title, page_title, meta_description, meta_keywords, manual_price, is_manual, is_active, show_in_summary, show_chart, logo, related_item_symbol, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $en_name, $symbol, $slug, $category, $sort_order, $description, $long_description, $h1_title, $page_title, $meta_description, $meta_keywords, $manual_price, $is_manual, $is_active, $show_in_summary, $show_chart, $logo, $related_item_symbol, $updated_at]);
                $id = $pdo->lastInsertId();
            }

            // Handle FAQs
            $pdo->prepare("DELETE FROM item_faqs WHERE item_id = ?")->execute([$id]);
            $faq_questions = $_POST['faq_questions'] ?? [];
            $faq_answers = $_POST['faq_answers'] ?? [];

            foreach ($faq_questions as $index => $question) {
                if (!empty($question) && !empty($faq_answers[$index])) {
                    $stmt = $pdo->prepare("INSERT INTO item_faqs (item_id, question, answer, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, $question, $faq_answers[$index], $index]);
                }
            }

            $pdo->commit();

            if (get_setting('lscache_purge_on_update', '1') === '1') {
                LSCache::purgeAll();
            }

            header("Location: items.php?message=success");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
        }
    }
}

// Fetch categories for dropdown
$categories = [];
if ($pdo) {
    try {
        $categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
    } catch (Exception $e) {}
}

// Fetch all items for related item dropdown
$all_items = [];
if ($pdo) {
    try {
        $all_items = $pdo->query("SELECT symbol, name FROM items ORDER BY name ASC")->fetchAll();
    } catch (Exception $e) {}
}

$page_title = $id ? 'ویرایش دارایی' : 'افزودن دارایی جدید';
$page_subtitle = 'تنظیمات قیمت، محتوای سئو و جزئیات دارایی';

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/editor.php';
?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Column -->
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                        <i data-lucide="package" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">اطلاعات پایه و قیمت</h2>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="form-group">
                            <label>نام دارایی (فارسی)</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($item['name'] ?? '') ?>" required placeholder="مثلاً طلای ۱۸ عیار">
                        </div>
                        <div class="form-group">
                            <label>نام انگلیسی</label>
                            <input type="text" name="en_name" value="<?= htmlspecialchars($item['en_name'] ?? '') ?>" placeholder="مثلاً 18k Gold">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="form-group">
                            <label>نماد در API (نوسان)</label>
                            <input type="text" name="symbol" value="<?= htmlspecialchars($item['symbol'] ?? '') ?>" required class="ltr-input" placeholder="مثلاً 18ayar">
                        </div>
                        <div class="form-group">
                            <label>نامک (Slug)</label>
                            <input type="text" name="slug" value="<?= htmlspecialchars($item['slug'] ?? $item['symbol'] ?? '') ?>" required class="ltr-input" placeholder="مثلاً gold-18k">
                        </div>
                        <div class="form-group">
                            <label>دسته‌بندی</label>
                            <select name="category">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= ($item['category'] ?? 'gold') === $cat['slug'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="form-group">
                            <label>قیمت دستی (تومان)</label>
                            <input type="text" name="manual_price" value="<?= htmlspecialchars($item['manual_price'] ?? '') ?>" placeholder="0" class="ltr-input">
                        </div>
                        <div class="form-group">
                            <label>ترتیب نمایش</label>
                            <input type="number" name="sort_order" value="<?= htmlspecialchars($item['sort_order'] ?? '0') ?>">
                        </div>
                        <div class="form-group">
                            <label>ارز مرتبط</label>
                            <select name="related_item_symbol">
                                <option value="">عدم نمایش</option>
                                <?php foreach ($all_items as $ai): ?>
                                    <?php if ($ai['symbol'] === ($item['symbol'] ?? '')) continue; ?>
                                    <option value="<?= htmlspecialchars($ai['symbol']) ?>" <?= ($item['related_item_symbol'] ?? '') === $ai['symbol'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ai['name']) ?> (<?= htmlspecialchars($ai['symbol']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">محتوای متنی</h2>
                </div>
                <div class="p-8">
                    <div class="form-group mb-6">
                        <label>توضیح کوتاه (بالای صفحه)</label>
                        <textarea name="description" id="item-description"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>توضیحات کامل (پایین صفحه)</label>
                        <textarea name="long_description" id="item-long_description"><?= htmlspecialchars($item['long_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-slate-400 border border-slate-100">
                            <i data-lucide="help-circle" class="w-5 h-5"></i>
                        </div>
                        <h2 class="text-lg font-black text-slate-800">سوالات متداول (FAQ)</h2>
                    </div>
                    <button type="button" onclick="addFaqRow()" class="btn-v3 btn-v3-outline !py-1 text-[10px]">
                        <i data-lucide="plus" class="w-3 h-3"></i> افزودن سوال
                    </button>
                </div>
                <div class="p-8" id="faq-container">
                    <?php if (empty($faqs)): ?>
                        <div class="faq-row grid grid-cols-1 gap-4 mb-6 p-4 bg-slate-50 rounded-lg relative group">
                            <button type="button" onclick="this.parentElement.remove()" class="absolute -left-2 -top-2 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                            <div class="form-group !mb-0">
                                <label class="text-[10px]">سوال</label>
                                <input type="text" name="faq_questions[]" placeholder="سوال خود را بنویسید...">
                            </div>
                            <div class="form-group !mb-0">
                                <label class="text-[10px]">پاسخ</label>
                                <textarea name="faq_answers[]" rows="2" placeholder="پاسخ سوال..."></textarea>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($faqs as $faq): ?>
                            <div class="faq-row grid grid-cols-1 gap-4 mb-6 p-4 bg-slate-50 rounded-lg relative group">
                                <button type="button" onclick="this.parentElement.remove()" class="absolute -left-2 -top-2 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                                <div class="form-group !mb-0">
                                    <label class="text-[10px]">سوال</label>
                                    <input type="text" name="faq_questions[]" value="<?= htmlspecialchars($faq['question']) ?>" placeholder="سوال خود را بنویسید...">
                                </div>
                                <div class="form-group !mb-0">
                                    <label class="text-[10px]">پاسخ</label>
                                    <textarea name="faq_answers[]" rows="2" placeholder="پاسخ سوال..."><?= htmlspecialchars($faq['answer']) ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Actions -->
            <div class="glass-card rounded-xl p-6 bg-slate-50/50 sticky top-4 z-10">
                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn-v3 btn-v3-primary w-full h-11 text-sm">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        ذخیره نهایی اطلاعات
                    </button>
                    <a href="items.php" class="btn-v3 btn-v3-outline w-full h-11 text-sm">انصراف</a>
                </div>
            </div>

            <!-- Image Upload -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="image" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">تصویر لوگو</h2>
                </div>
                <div class="p-6">
                    <div class="form-group mb-4">
                        <div class="file-input-wrapper">
                            <div class="file-input-custom">
                                <span class="file-name-label text-[10px] text-slate-400 truncate">انتخاب فایل تصویر...</span>
                                <i data-lucide="upload-cloud" class="w-4 h-4 text-slate-400"></i>
                                <input type="file" name="logo_file" class="file-input-real">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="text-[10px]">یا لینک مستقیم تصویر</label>
                        <input type="text" name="logo_url" value="<?= htmlspecialchars($item['logo'] ?? '') ?>" class="ltr-input text-xs" placeholder="https://...">
                    </div>
                    <input type="hidden" name="current_logo" value="<?= htmlspecialchars($item['logo'] ?? '') ?>">
                </div>
            </div>

            <!-- Manual Date -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">زمان بروزرسانی</h2>
                </div>
                <div class="p-6">
                    <div class="form-group mb-0">
                        <label class="text-[10px]">تاریخ شمسی</label>
                        <div class="input-icon-wrapper">
                            <span class="icon"><i data-lucide="calendar" class="w-3.5 h-3.5"></i></span>
                            <input type="text" id="updated_at_picker" class="font-bold cursor-pointer text-xs" placeholder="انتخاب تاریخ...">
                        </div>
                        <input type="hidden" name="updated_at" id="updated_at_value" value="<?= htmlspecialchars($item['updated_at'] ?? '') ?>">
                        <p class="text-[9px] text-slate-400 mt-2 leading-relaxed">این تاریخ در نقشه سایت (Lastmod) استفاده می‌شود.</p>
                    </div>
                </div>
            </div>

            <!-- SEO Settings -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">تنظیمات SEO</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-group">
                        <label class="text-[10px]">عنوان اصلی (H1)</label>
                        <input type="text" name="h1_title" value="<?= htmlspecialchars($item['h1_title'] ?? '') ?>" placeholder="مثلاً قیمت لحظه‌ای طلا">
                    </div>
                    <div class="form-group">
                        <label class="text-[10px]">عنوان مرورگر (Title)</label>
                        <input type="text" name="page_title" value="<?= htmlspecialchars($item['page_title'] ?? '') ?>" placeholder="Meta Title">
                    </div>
                    <div class="form-group">
                        <label class="text-[10px]">توضیحات متا</label>
                        <textarea name="meta_description" rows="3" class="text-xs" placeholder="توضیحات سئو..."><?= htmlspecialchars($item['meta_description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="text-[10px]">کلمات کلیدی</label>
                        <div id="keywords-container" class="flex flex-wrap gap-2 p-2 bg-white border border-slate-200 rounded-lg min-h-[42px] mb-2">
                            <input type="text" id="keyword-input" class="!border-none !p-0 !ring-0 text-[10px] flex-grow min-w-[100px]" placeholder="تایپ کنید...">
                        </div>
                        <input type="hidden" name="meta_keywords" id="item-meta_keywords" value="<?= htmlspecialchars($item['meta_keywords'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Other Settings -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="sliders" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">سایر تنظیمات</h2>
                </div>
                <div class="p-6 space-y-4">
                    <label class="relative inline-flex items-center cursor-pointer group w-full">
                        <input type="checkbox" name="is_active" class="sr-only peer" <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <div class="toggle-dot toggle-emerald"></div>
                        <span class="mr-3 text-[11px] font-black text-slate-600 group-hover:text-slate-900 transition-colors">وضعیت فعال</span>
                    </label>
                    <label class="relative inline-flex items-center cursor-pointer group w-full">
                        <input type="checkbox" name="is_manual" class="sr-only peer" <?= ($item['is_manual'] ?? 0) ? 'checked' : '' ?>>
                        <div class="toggle-dot"></div>
                        <span class="mr-3 text-[11px] font-black text-slate-600 group-hover:text-slate-900 transition-colors">قیمت دستی</span>
                    </label>
                    <label class="relative inline-flex items-center cursor-pointer group w-full">
                        <input type="checkbox" name="show_in_summary" class="sr-only peer" <?= ($item['show_in_summary'] ?? 0) ? 'checked' : '' ?>>
                        <div class="toggle-dot toggle-indigo"></div>
                        <span class="mr-3 text-[11px] font-black text-slate-600 group-hover:text-slate-900 transition-colors">نمایش در خلاصه</span>
                    </label>
                    <label class="relative inline-flex items-center cursor-pointer group w-full">
                        <input type="checkbox" name="show_chart" class="sr-only peer" <?= ($item['show_chart'] ?? 0) ? 'checked' : '' ?>>
                        <div class="toggle-dot toggle-amber"></div>
                        <span class="mr-3 text-[11px] font-black text-slate-600 group-hover:text-slate-900 transition-colors">نمایش در نمودار</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="faq-template">
    <div class="faq-row grid grid-cols-1 gap-4 mb-6 p-4 bg-slate-50 rounded-lg relative group">
        <button type="button" onclick="this.parentElement.remove()" class="absolute -left-2 -top-2 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
            <i data-lucide="x" class="w-3 h-3"></i>
        </button>
        <div class="form-group !mb-0">
            <label class="text-[10px]">سوال</label>
            <input type="text" name="faq_questions[]" placeholder="سوال خود را بنویسید...">
        </div>
        <div class="form-group !mb-0">
            <label class="text-[10px]">پاسخ</label>
            <textarea name="faq_answers[]" rows="2" placeholder="پاسخ سوال..."></textarea>
        </div>
    </div>
</template>

<script>
    // Keywords Tags Logic
    const keywordInput = document.getElementById('keyword-input');
    const keywordsContainer = document.getElementById('keywords-container');
    const metaKeywordsHidden = document.getElementById('item-meta_keywords');
    let keywords = metaKeywordsHidden.value ? metaKeywordsHidden.value.split(',') : [];

    function renderKeywords() {
        const tagsElements = keywordsContainer.querySelectorAll('.keyword-tag');
        tagsElements.forEach(el => el.remove());

        keywords.forEach((tag, index) => {
            if (!tag.trim()) return;
            const tagEl = document.createElement('span');
            tagEl.className = 'keyword-tag inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-[11px] font-bold border border-indigo-100 shadow-sm';

            const textNode = document.createTextNode(tag);
            tagEl.appendChild(textNode);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'remove-btn text-current/50 hover:text-rose-500 transition-colors';
            btn.innerHTML = '<i data-lucide="x" style="width: 12px; height: 12px;"></i>';
            btn.onclick = (e) => {
                e.preventDefault();
                removeTag(index);
            };

            tagEl.appendChild(btn);
            keywordsContainer.insertBefore(tagEl, keywordInput);
        });

        metaKeywordsHidden.value = keywords.join(',');
        if (window.refreshIcons) window.refreshIcons();
    }

    function removeTag(index) {
        keywords.splice(index, 1);
        renderKeywords();
    }

    function addKeywords(value) {
        const parts = value.split(',').map(p => p.trim()).filter(p => p !== '');
        let changed = false;
        parts.forEach(val => {
            if (val && !keywords.includes(val)) {
                keywords.push(val);
                changed = true;
            }
        });
        if (changed) {
            renderKeywords();
            keywordInput.value = '';
        }
    }

    keywordInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addKeywords(keywordInput.value);
        } else if (e.key === 'Backspace' && keywordInput.value === '' && keywords.length > 0) {
            keywords.pop();
            renderKeywords();
        }
    });

    keywordInput.addEventListener('input', (e) => {
        if (keywordInput.value.includes(',')) {
            addKeywords(keywordInput.value);
        }
    });

    keywordsContainer.addEventListener('click', () => {
        keywordInput.focus();
    });

    renderKeywords();

    function addFaqRow() {
        const container = document.getElementById('faq-container');
        const template = document.getElementById('faq-template');
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        window.refreshIcons();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initTinyMCE('#item-description');
        initTinyMCE('#item-long_description');

        // Initialize Persian Datepicker
        const initialDate = $('#updated_at_value').val();
        $('#updated_at_picker').persianDatepicker({
            format: 'YYYY/MM/DD HH:mm:ss',
            altField: '#updated_at_value',
            altFormat: 'YYYY-MM-DD HH:mm:ss',
            timePicker: {
                enabled: true,
                second: {
                    enabled: false
                }
            }
        });

        if (initialDate && initialDate !== '0000-00-00 00:00:00' && initialDate !== '0000-00-00') {
            let d = new Date(initialDate.replace(/-/g, "/"));
            if (isNaN(d.getTime())) d = new Date();
            const pDate = new persianDate(d);
            $('#updated_at_picker').val(pDate.format('YYYY/MM/DD HH:mm:ss'));
        } else {
            const pDate = new persianDate();
            $('#updated_at_picker').val(pDate.format('YYYY/MM/DD HH:mm:ss'));
            $('#updated_at_value').val(pDate.format('YYYY-MM-DD HH:mm:ss'));
        }
    });
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
