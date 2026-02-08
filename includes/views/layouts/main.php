<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site_title ?? 'طلا آنلاین') ?> | قیمت لحظه‌ای طلا، سکه و ارز</title>
    <meta name="description" content="<?= htmlspecialchars($site_description ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($site_keywords ?? '') ?>">
    <link rel="canonical" href="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="og:title" content="<?= htmlspecialchars($site_title ?? '') ?> | قیمت لحظه‌ای طلا، سکه و ارز">
    <meta property="og:description" content="<?= htmlspecialchars($site_description ?? '') ?>">
    <meta property="og:image" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/assets/images/logo.svg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($site_title ?? '') ?> | قیمت لحظه‌ای طلا، سکه و ارز">
    <meta property="twitter:description" content="<?= htmlspecialchars($site_description ?? '') ?>">
    <meta property="twitter:image" content="<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/assets/images/logo.svg">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?= htmlspecialchars($site_title ?? 'طلا آنلاین') ?>",
      "url": "<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>",
      "description": "<?= htmlspecialchars($site_description ?? '') ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]" ?>/?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#1d4ed8', // Blue from image
                        secondary: '#f59e0b', // Orange from image
                        'primary-light': '#3b82f6',
                        'primary-dark': '#1e40af',
                        surface: {
                            light: '#ffffff',
                            dark: '#1e293b'
                        },
                        background: {
                            light: '#f8fafc',
                            dark: '#0f172a'
                        }
                    },
                    fontFamily: {
                        vazir: ['Vazirmatn', 'sans-serif'],
                    },
                    borderRadius: {
                        '2xl': '1rem',
                        '3xl': '1.5rem',
                        '4xl': '2rem',
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                        'hover': '0 10px 30px -5px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply font-vazir bg-background-light text-slate-600 dark:bg-background-dark dark:text-slate-400 antialiased;
            }
        }
        @layer components {
            .glass-card {
                @apply bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 shadow-soft rounded-3xl p-6 transition-all duration-300;
            }
            .sidebar-item {
                @apply flex items-center justify-center w-12 h-12 rounded-2xl text-slate-400 hover:text-primary hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-all duration-200;
            }
            .sidebar-item.active {
                @apply bg-primary text-white shadow-lg shadow-primary/30;
            }
            .tab-item {
                @apply px-6 py-2 rounded-full text-sm font-bold transition-all duration-200;
            }
            .tab-item.active {
                @apply bg-primary text-white shadow-md shadow-primary/20;
            }
            .tab-item:not(.active) {
                @apply text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800;
            }
            .btn-upgrade {
                @apply bg-secondary hover:bg-amber-600 text-white font-bold py-2.5 px-6 rounded-full transition-all duration-300 flex items-center gap-2 shadow-lg shadow-secondary/30;
            }
        }
    </style>
</head>
<body class="overflow-x-hidden">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 right-0 z-50 w-24 border-l border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col items-center py-8 transition-transform duration-300 lg:translate-x-0 lg:static lg:h-screen lg:z-auto translate-x-full">
            <!-- Logo -->
            <div class="mb-12">
                <div class="w-12 h-12 rounded-full border-4 border-slate-900 dark:border-white flex items-center justify-center">
                    <div class="w-6 h-6 rounded-full border-2 border-slate-900 dark:border-white"></div>
                </div>
            </div>

            <!-- Nav Icons -->
            <div class="flex flex-col gap-6 flex-grow">
                <a href="#" class="sidebar-item" aria-label="خانه"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></a>
                <a href="#" class="sidebar-item" aria-label="کیف پول"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg></a>
                <a href="#" class="sidebar-item active" aria-label="تحلیل"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></a>
                <a href="#" class="sidebar-item" aria-label="آمار"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20v-6M6 20V10M18 20V4"></path></svg></a>
                <a href="#" class="sidebar-item" aria-label="تنظیمات"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></a>
            </div>

            <!-- Bottom Actions -->
            <div class="flex flex-col gap-4 mt-auto">
                <button id="theme-toggle" class="sidebar-item" aria-label="تغییر تم">
                    <svg class="sun-icon block dark:hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg class="moon-icon hidden dark:block" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>
                <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden">
                    <img src="https://i.pravatar.cc/100" alt="User">
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

        <!-- Main Content -->
        <main class="flex-grow flex flex-col min-w-0">
            <!-- Header -->
            <header class="bg-white/50 dark:bg-slate-900/50 backdrop-blur-md sticky top-0 z-40 px-4 lg:px-8 py-6 flex flex-col md:flex-row justify-between items-center gap-6">
                <!-- Mobile Menu Toggle -->
                <button class="lg:hidden p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400" onclick="toggleSidebar()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>

                <!-- Tabs -->
                <div class="flex bg-slate-100/50 dark:bg-slate-800/50 p-1.5 rounded-full border border-slate-200/20">
                    <a href="#" class="tab-item active">نمای کلی</a>
                    <a href="#" class="tab-item">منابع</a>
                    <a href="#" class="tab-item">تخصیص</a>
                </div>

                <!-- Search & Profile -->
                <div class="flex items-center gap-6 w-full md:w-auto">
                    <div class="relative flex-grow md:w-80 group">
                        <input type="text" placeholder="جستجو در بازار..." class="w-full bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 rounded-full py-3 pr-12 pl-4 text-sm focus:bg-white focus:ring-4 focus:ring-primary/5 outline-none transition-all">
                        <svg class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </div>

                    <button class="relative text-slate-400 hover:text-slate-600 transition-colors">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <span class="absolute top-0 left-0 w-2 h-2 bg-rose-500 rounded-full border-2 border-white dark:border-slate-900"></span>
                    </button>

                    <button class="btn-upgrade group">
                        <div class="bg-amber-400 group-hover:bg-amber-300 p-1 rounded-lg transition-colors">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-white"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                        </div>
                        <span>ارتقاء اکانت</span>
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8">
                <?= $content ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
    <script src="assets/js/charts.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
