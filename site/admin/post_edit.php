<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
check_login();

$id = $_GET['id'] ?? null;
$post = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $excerpt = $_POST['excerpt'] ?? '';
        $content = $_POST['content'] ?? '';
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $status = $_POST['status'] ?? 'draft';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $meta_title = $_POST['meta_title'] ?? '';
        $meta_description = $_POST['meta_description'] ?? '';
        $meta_keywords = $_POST['meta_keywords'] ?? '';
        $tags = $_POST['tags'] ?? '';

        $created_at = !empty($_POST['created_at']) ? $_POST['created_at'] : ($post['created_at'] ?? date('Y-m-d H:i:s'));
        $updated_at = date('Y-m-d H:i:s'); // Always update last modified

        // Handle Thumbnail Upload
        $thumbnail = $_POST['current_thumbnail'] ?? '';
        if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_upload($_FILES['thumbnail_file']);
            if ($uploaded_path) {
                $thumbnail = $uploaded_path;
            }
        } elseif (!empty($_POST['thumbnail_url'])) {
            $thumbnail = $_POST['thumbnail_url'];
        }

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, thumbnail = ?, category_id = ?, status = ?, is_featured = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, tags = ?, created_at = ?, updated_at = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $excerpt, $content, $thumbnail, $category_id, $status, $is_featured, $meta_title, $meta_description, $meta_keywords, $tags, $created_at, $updated_at, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, thumbnail, category_id, status, is_featured, meta_title, meta_description, meta_keywords, tags, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $excerpt, $content, $thumbnail, $category_id, $status, $is_featured, $meta_title, $meta_description, $meta_keywords, $tags, $created_at, $updated_at]);
            }

            header("Location: posts.php?message=success");
            exit;
        } catch (Exception $e) {
            $error = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
        }
    }
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM blog_categories ORDER BY sort_order ASC")->fetchAll();

$page_title = $id ? 'ویرایش مقاله' : 'افزودن مقاله جدید';
$page_subtitle = 'نوشتن محتوا، تنظیمات سئو و انتشار مقاله';

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
                        <i data-lucide="edit-3" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">محتوای مقاله</h2>
                </div>
                <div class="p-8 space-y-6">
                    <div class="form-group">
                        <label>عنوان مقاله</label>
                        <input type="text" name="title" id="post-title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required placeholder="عنوانی جذاب بنویسید...">
                    </div>

                    <div class="form-group">
                        <label>خلاصه مقاله (Excerpt)</label>
                        <textarea name="excerpt" rows="3" placeholder="خلاصه‌ای کوتاه برای نمایش در لیست مقالات..."><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>محتوای کامل</label>
                        <textarea name="content" id="post-content"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="text-[11px] font-black">برچسب‌های مقاله</label>
                        <div id="tags-container" class="flex flex-wrap gap-2 p-3 bg-white border border-slate-200 rounded-lg min-h-[46px] mb-2 cursor-text">
                            <input type="text" id="tag-input" class="!border-none !p-0 !ring-0 text-[11px] flex-grow min-w-[120px]" placeholder="تایپ کنید و اینتر بزنید...">
                        </div>
                        <input type="hidden" name="tags" id="post-tags" value="<?= htmlspecialchars($post['tags'] ?? '') ?>">
                        <p class="text-[9px] text-slate-400">برچسب‌ها برای دسته‌بندی غیررسمی و نمایش در انتهای مقاله استفاده می‌شوند.</p>
                    </div>
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
                        ذخیره نهایی مقاله
                    </button>
                    <a href="posts.php" class="btn-v3 btn-v3-outline w-full h-11 text-sm">انصراف</a>
                </div>
            </div>

            <!-- Publish Settings -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="send" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">تنظیمات انتشار</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-group">
                        <label class="text-[10px]">وضعیت انتشار</label>
                        <select name="status">
                            <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option>
                            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>منتشر شده</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="text-[10px]">دسته‌بندی</label>
                        <select name="category_id">
                            <option value="">بدون دسته</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer group w-full">
                        <input type="checkbox" name="is_featured" class="sr-only peer" <?= ($post['is_featured'] ?? 0) ? 'checked' : '' ?>>
                        <div class="toggle-dot toggle-amber"></div>
                        <span class="mr-3 text-[11px] font-black text-slate-600 group-hover:text-slate-900 transition-colors">مقاله ویژه (Featured)</span>
                    </label>
                </div>
            </div>

            <!-- Slug & URL -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="link" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">آدرس مقاله (URL)</h2>
                </div>
                <div class="p-6">
                    <div class="form-group mb-0">
                        <label class="text-[10px]">نامک (Slug)</label>
                        <input type="text" name="slug" id="post-slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" required class="ltr-input text-xs" placeholder="my-blog-post">
                        <p class="text-[9px] text-slate-400 mt-2">آدرس نهایی: <?= get_base_url() ?>/blog/<span id="slug-preview"><?= $post['slug'] ?? '...' ?></span></p>
                    </div>
                </div>
            </div>

            <!-- Thumbnail -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="image" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">تصویر شاخص</h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($post['thumbnail'])): ?>
                        <div class="mb-4 rounded-lg overflow-hidden border border-slate-100">
                            <img src="../<?= htmlspecialchars($post['thumbnail']) ?>" alt="" class="w-full h-auto">
                        </div>
                    <?php endif; ?>
                    <div class="form-group mb-4">
                        <div class="file-input-wrapper">
                            <div class="file-input-custom">
                                <span class="file-name-label text-[10px] text-slate-400 truncate">انتخاب تصویر...</span>
                                <i data-lucide="upload-cloud" class="w-4 h-4 text-slate-400"></i>
                                <input type="file" name="thumbnail_file" class="file-input-real">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="text-[10px]">یا لینک مستقیم</label>
                        <input type="text" name="thumbnail_url" value="<?= htmlspecialchars($post['thumbnail'] ?? '') ?>" class="ltr-input text-xs" placeholder="https://...">
                    </div>
                    <input type="hidden" name="current_thumbnail" value="<?= htmlspecialchars($post['thumbnail'] ?? '') ?>">
                </div>
            </div>

            <!-- Dates -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">تاریخ‌ها</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-group mb-0">
                        <label class="text-[10px]">تاریخ انتشار (شمسی)</label>
                        <div class="input-icon-wrapper">
                            <span class="icon"><i data-lucide="calendar" class="w-3.5 h-3.5"></i></span>
                            <input type="text" id="created_at_picker" class="font-bold cursor-pointer text-xs" placeholder="انتخاب تاریخ...">
                        </div>
                        <input type="hidden" name="created_at" id="created_at_value" value="<?= htmlspecialchars($post['created_at'] ?? '') ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-[10px]">آخرین بروزرسانی</label>
                        <div class="input-icon-wrapper">
                            <span class="icon"><i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i></span>
                            <input type="text" id="updated_at_picker" class="font-bold cursor-pointer text-xs" placeholder="انتخاب تاریخ...">
                        </div>
                        <input type="hidden" name="updated_at" id="updated_at_value" value="<?= htmlspecialchars($post['updated_at'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- SEO -->
            <div class="glass-card rounded-xl overflow-hidden border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/30">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                    <h2 class="text-sm font-black text-slate-800">تنظیمات SEO</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-group">
                        <label class="text-[10px]">عنوان سئو (Title)</label>
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>" placeholder="Meta Title">
                    </div>
                    <div class="form-group">
                        <label class="text-[10px]">توضیحات متا</label>
                        <textarea name="meta_description" rows="3" class="text-xs" placeholder="توضیحات سئو..."><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="text-[10px]">کلمات کلیدی</label>
                        <div id="keywords-container" class="flex flex-wrap gap-2 p-2 bg-white border border-slate-200 rounded-lg min-h-[42px] mb-2">
                            <input type="text" id="keyword-input" class="!border-none !p-0 !ring-0 text-[10px] flex-grow min-w-[100px]" placeholder="تایپ کنید...">
                        </div>
                        <input type="hidden" name="meta_keywords" id="post-meta_keywords" value="<?= htmlspecialchars($post['meta_keywords'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Slug Generation
    const titleInput = document.getElementById('post-title');
    const slugInput = document.getElementById('post-slug');
    const slugPreview = document.getElementById('slug-preview');

    titleInput.addEventListener('input', () => {
        if (!slugInput.dataset.manual) {
            const slug = titleInput.value
                .toLowerCase()
                .replace(/[^\u0600-\u06FFa-z0-9\s-]/g, '') // Keep Persian, English, numbers, spaces, hyphens
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            slugInput.value = slug;
            slugPreview.innerText = slug || '...';
        }
    });

    slugInput.addEventListener('input', () => {
        slugInput.dataset.manual = true;
        slugPreview.innerText = slugInput.value || '...';
    });

    // Generic Tag Input Logic
    function setupTagInput(inputId, containerId, hiddenId) {
        const input = document.getElementById(inputId);
        const container = document.getElementById(containerId);
        const hidden = document.getElementById(hiddenId);
        let tags = hidden.value ? hidden.value.split(',').filter(t => t.trim() !== '') : [];

        function render() {
            container.querySelectorAll('.tag-item').forEach(el => el.remove());

            tags.forEach((tag, index) => {
                const tagEl = document.createElement('span');
                tagEl.className = 'tag-item inline-flex items-center gap-1 px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[10px] font-bold border border-indigo-100';

                // Use textContent for the tag text to prevent XSS
                const textNode = document.createTextNode(tag + ' ');
                tagEl.appendChild(textNode);

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'remove-btn hover:text-rose-500 transition-colors';
                btn.innerHTML = '<i data-lucide="x" class="w-3 h-3"></i>';

                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    tags.splice(index, 1);
                    render();
                });

                container.insertBefore(tagEl, input);
            });

            hidden.value = tags.join(',');
            if (window.refreshIcons) window.refreshIcons();
        }

        function addTags(value) {
            const parts = value.split(',').map(p => p.trim()).filter(p => p !== '');
            let changed = false;
            parts.forEach(val => {
                if (val && !tags.includes(val)) {
                    tags.push(val);
                    changed = true;
                }
            });
            if (changed) {
                render();
                input.value = '';
            }
        }

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addTags(input.value);
            } else if (e.key === 'Backspace' && input.value === '' && tags.length > 0) {
                tags.pop();
                render();
            }
        });

        input.addEventListener('input', (e) => {
            if (input.value.includes(',')) {
                addTags(input.value);
            }
        });

        container.addEventListener('click', () => {
            input.focus();
        });

        render();
    }

    // Initialize Tag Inputs
    setupTagInput('keyword-input', 'keywords-container', 'post-meta_keywords');
    setupTagInput('tag-input', 'tags-container', 'post-tags');

    $(document).ready(function() {
        initTinyMCE('#post-content');

        // File Input Label
        $('.file-input-real').on('change', function() {
            const fileName = $(this).val().split('\\').pop();
            $(this).closest('.file-input-wrapper').find('.file-name-label').text(fileName || 'انتخاب تصویر...');
        });

        // Datepickers
        const setupDatePicker = (pickerId, valId) => {
            const initialDate = $(valId).val();
            $(pickerId).persianDatepicker({
                format: 'YYYY/MM/DD HH:mm:ss',
                altField: valId,
                altFormat: 'YYYY-MM-DD HH:mm:ss',
                timePicker: { enabled: true, second: { enabled: false } }
            });
            if (initialDate) {
                const pDate = new persianDate(new Date(initialDate));
                $(pickerId).val(pDate.format('YYYY/MM/DD HH:mm:ss'));
            }
        };

        setupDatePicker('#created_at_picker', '#created_at_value');
        setupDatePicker('#updated_at_picker', '#updated_at_value');
    });
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
