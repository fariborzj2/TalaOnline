    </main>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Global function to update icons if dynamic content is added
    window.refreshIcons = () => {
        lucide.createIcons();
    }

    // Custom File Input Label Handler
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('file-input-real')) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'انتخاب تصویر...';
            const label = e.target.closest('.file-input-custom').querySelector('.file-name-label');
            if (label) label.textContent = fileName;
        }
    });

    // Global form submission loading state
    document.addEventListener('submit', function(e) {
        if (e.target.tagName === 'FORM') {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('btn-loading');
            }
        }
    });

</script>

<!-- Custom Dialog Modal -->
<div id="customDialogOverlay" class="custom-dialog-overlay">
    <div class="custom-dialog-box">
        <div id="dialogIconContainer" class="w-16 h-16 rounded-2xl mx-auto mb-6 flex items-center justify-center">
            <i id="dialogIcon" data-lucide="info" class="w-8 h-8"></i>
        </div>
        <h3 id="dialogTitle" class="text-lg font-black text-slate-900 mb-2"></h3>
        <p id="dialogMessage" class="text-sm text-slate-500 font-bold leading-relaxed mb-8"></p>
        <div id="dialogActions" class="flex flex-col gap-3">
            <button id="dialogConfirmBtn" class="btn-v3 btn-v3-primary w-full !py-3 !text-sm">تایید</button>
            <button id="dialogCancelBtn" class="btn-v3 btn-v3-outline w-full !py-3 !text-sm">انصراف</button>
        </div>
    </div>
</div>

<script>
    // Custom Dialog Logic
    const dialogOverlay = document.getElementById('customDialogOverlay');
    const dialogIconContainer = document.getElementById('dialogIconContainer');
    const dialogIcon = document.getElementById('dialogIcon');
    const dialogTitle = document.getElementById('dialogTitle');
    const dialogMessage = document.getElementById('dialogMessage');
    const dialogConfirmBtn = document.getElementById('dialogConfirmBtn');
    const dialogCancelBtn = document.getElementById('dialogCancelBtn');

    window.showAlert = function(message, type = 'info', title = '') {
        return new Promise((resolve) => {
            const types = {
                'success': { icon: 'check-circle', color: 'bg-emerald-50 text-emerald-600', btn: 'bg-emerald-600 hover:bg-emerald-700', title: title || 'موفقیت‌آمیز' },
                'error': { icon: 'x-circle', color: 'bg-rose-50 text-rose-600', btn: 'bg-rose-600 hover:bg-rose-700', title: title || 'خطا' },
                'warning': { icon: 'alert-triangle', color: 'bg-amber-50 text-amber-600', btn: 'bg-amber-600 hover:bg-amber-700', title: title || 'هشدار' },
                'info': { icon: 'info', color: 'bg-indigo-50 text-indigo-600', btn: 'bg-indigo-600 hover:bg-indigo-700', title: title || 'پیام سیستم' }
            };

            const config = types[type] || types.info;

            dialogIcon.setAttribute('data-lucide', config.icon);
            dialogIconContainer.className = `w-16 h-16 rounded-2xl mx-auto mb-6 flex items-center justify-center ${config.color}`;
            dialogTitle.innerText = config.title;
            dialogMessage.innerText = message;
            dialogConfirmBtn.className = `btn-v3 text-white w-full !py-3 !text-sm transition-all ${config.btn}`;
            dialogConfirmBtn.innerText = 'متوجه شدم';
            dialogCancelBtn.classList.add('hidden');

            window.refreshIcons();
            dialogOverlay.classList.add('active');

            const close = () => {
                dialogOverlay.classList.remove('active');
                resolve();
            };

            dialogConfirmBtn.onclick = close;
        });
    };

    window.showConfirm = function(message, title = 'آیا اطمینان دارید؟') {
        return new Promise((resolve) => {
            dialogIcon.setAttribute('data-lucide', 'help-circle');
            dialogIconContainer.className = `w-16 h-16 rounded-2xl mx-auto mb-6 flex items-center justify-center bg-indigo-50 text-indigo-600`;
            dialogTitle.innerText = title;
            dialogMessage.innerText = message;
            dialogConfirmBtn.className = `btn-v3 btn-v3-primary w-full !py-3 !text-sm`;
            dialogConfirmBtn.innerText = 'بله، انجام شود';
            dialogCancelBtn.classList.remove('hidden');

            window.refreshIcons();
            dialogOverlay.classList.add('active');

            dialogConfirmBtn.onclick = () => {
                dialogOverlay.classList.remove('active');
                resolve(true);
            };

            dialogCancelBtn.onclick = () => {
                dialogOverlay.classList.remove('active');
                resolve(false);
            };
        });
    };
</script>
</body>
</html>
