document.addEventListener('DOMContentLoaded', function() {
    const persianNumberFormatter = new Intl.NumberFormat('fa-IR');
    const toPersianDigits = (num) => {
        if (num === null || num === undefined) return '';
        return persianNumberFormatter.format(num);
    };

    const formatPrice = (price) => toPersianDigits(price);

    const convertDigitsToEnglish = (str) => {
        if (!str || typeof str !== 'string') return str;
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        let result = str;
        for (let i = 0; i < 10; i++) {
            result = result.replace(new RegExp(persian[i], 'g'), english[i]);
            result = result.replace(new RegExp(arabic[i], 'g'), english[i]);
        }
        return result;
    };

    window.togglePassword = function(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i') || btn.querySelector('svg');
        if (!input || !icon) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        if (window.lucide) window.lucide.createIcons({ root: btn });
    };

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
            if (!dialogOverlay) {
                alert(message);
                resolve();
                return;
            }
            const types = {
                'success': { icon: 'check-circle', class: 'success', title: title || 'موفقیت‌آمیز' },
                'error': { icon: 'x-circle', class: 'error', title: title || 'خطا' },
                'warning': { icon: 'alert-triangle', class: 'warning', title: title || 'هشدار' },
                'info': { icon: 'info', class: 'info', title: title || 'پیام سیستم' }
            };

            const config = types[type] || types.info;

            dialogIcon.setAttribute('data-lucide', config.icon);
            dialogIconContainer.className = `dialog-icon-container ${config.class}`;
            dialogTitle.innerText = config.title;
            dialogMessage.innerText = message;
            dialogConfirmBtn.innerText = 'متوجه شدم';
            dialogCancelBtn.classList.add('d-none');

            if (window.lucide) window.lucide.createIcons({ root: dialogIconContainer });
            dialogOverlay.classList.add('active');

            const close = () => {
                const box = dialogOverlay.querySelector('.custom-dialog-box');
                dialogOverlay.classList.add('closing');
                if (box) box.classList.add('closing');
                setTimeout(() => {
                    dialogOverlay.classList.remove('active');
                    dialogOverlay.classList.remove('closing');
                    if (box) box.classList.remove('closing');
                    resolve();
                }, 300);
            };

            dialogConfirmBtn.onclick = close;
        });
    };

    window.showConfirm = function(message, title = 'آیا اطمینان دارید؟') {
        return new Promise((resolve) => {
            if (!dialogOverlay) {
                resolve(confirm(message));
                return;
            }
            dialogIcon.setAttribute('data-lucide', 'help-circle');
            dialogIconContainer.className = `dialog-icon-container info`;
            dialogTitle.innerText = title;
            dialogMessage.innerText = message;
            dialogConfirmBtn.innerText = 'بله، انجام شود';
            dialogCancelBtn.classList.remove('d-none');

            if (window.lucide) window.lucide.createIcons({ root: dialogIconContainer });
            dialogOverlay.classList.add('active');

            const close = (result) => {
                const box = dialogOverlay.querySelector('.custom-dialog-box');
                dialogOverlay.classList.add('closing');
                if (box) box.classList.add('closing');
                setTimeout(() => {
                    dialogOverlay.classList.remove('active');
                    dialogOverlay.classList.remove('closing');
                    if (box) box.classList.remove('closing');
                    resolve(result);
                }, 300);
            };

            dialogConfirmBtn.onclick = () => close(true);
            dialogCancelBtn.onclick = () => close(false);
        });
    };

    const getAssetUrl = (path) => {
        if (!path) return '/assets/images/gold/gold.webp';
        if (path.startsWith('http')) return path;
        let clean = path.startsWith('/') ? path : '/' + path;
        return clean.replace(/\.(png|jpg|jpeg)$/i, '.webp');
    };

    const populatePlatforms = (platforms) => {
        const list = document.getElementById('platforms-list');
        if (!list) return;

        // Calculate best buy/sell
        let minBuy = null;
        let maxSell = null;
        platforms.forEach(p => {
            const buy = parseFloat(p.buy_price || 0);
            const sell = parseFloat(p.sell_price || 0);
            const fee = parseFloat(p.fee || 0);
            const effBuy = buy * (1 + fee / 100);
            const effSell = sell * (1 - fee / 100);
            if (minBuy === null || effBuy < minBuy) minBuy = effBuy;
            if (maxSell === null || effSell > maxSell) maxSell = effSell;
        });

        list.innerHTML = platforms.map(platform => {
            const buy = parseFloat(platform.buy_price || 0);
            const sell = parseFloat(platform.sell_price || 0);
            const fee = parseFloat(platform.fee || 0);
            const effBuy = buy * (1 + fee / 100);
            const effSell = sell * (1 - fee / 100);

            let statusText = 'عادی';
            let statusClass = 'warning';

            if (minBuy !== null && effBuy <= minBuy) {
                statusText = 'مناسب خرید';
                statusClass = 'success';
            } else if (maxSell !== null && effSell >= maxSell) {
                statusText = 'مناسب فروش';
                statusClass = 'info';
            }

            return `
                <tr>
                    <td>
                        <div class="brand-logo"> <img src="${getAssetUrl(platform.logo)}" alt="${platform.name}" loading="lazy" decoding="async" width="32" height="32"> </div>
                    </td>
                    <td>
                        <div class="line20">
                            <div class="text-title">${platform.name}</div>
                            <div class="font-size-0-8">${platform.en_name || ''}</div>
                        </div>
                    </td>
                    <td class="font-size-2 font-bold text-title">${formatPrice(platform.buy_price)}</td>
                    <td class="font-size-2 font-bold text-title">${formatPrice(platform.sell_price)}</td>
                    <td class="font-size-2 " dir="ltr">${toPersianDigits(platform.fee)}%</td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td>
                        <a href="${platform.link}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">
                            <i data-lucide="external-link" class="h-4 w-4"></i> خرید طلا
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
        if (window.lucide) window.lucide.createIcons({ root: list });
    };

    const initSearch = (platforms) => {
        const searchInput = document.getElementById('platform-search');
        if (!searchInput) return;
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = platforms.filter(p =>
                p.name.toLowerCase().includes(query) ||
                (p.en_name && p.en_name.toLowerCase().includes(query))
            );
            populatePlatforms(filtered);
        });
    };

    const enhanceContent = () => {
        // Global numeral conversion for all forms
        document.addEventListener('submit', (e) => {
            if (e.target.tagName === 'FORM') {
                const numericFields = e.target.querySelectorAll('input[type="tel"], input[type="number"], input[name*="phone"], input[name*="code"], input[name*="price"], input[name*="fee"], input[name*="limit"], input[name*="interval"], input[name*="count"], input[name*="port"]');
                numericFields.forEach(field => {
                    field.value = convertDigitsToEnglish(field.value);
                });
            }
        });

        document.querySelectorAll('.content-text').forEach(area => {
            area.querySelectorAll('table').forEach(table => {
                if (!table.parentElement.classList.contains('table-wrapper')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-wrapper';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });

            const tocPlaceholder = area.querySelector('#toc-placeholder') || document.getElementById('toc-container-main');
            const headings = area.querySelectorAll('h2, h3');
            if (tocPlaceholder && headings.length > 1) {
                const isSidebar = tocPlaceholder.id === 'toc-container-main';
                const tocList = document.createElement('ul');
                tocList.className = isSidebar ? 'd-column gap-02 mt-1' : 'toc-list';
                const targetContainer = isSidebar ? document.getElementById('toc-content') : tocPlaceholder;

                headings.forEach((heading, index) => {
                    const id = `heading-${index}`;
                    heading.id = id;
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = `#${id}`;
                    a.textContent = heading.textContent;
                    a.className = isSidebar ? 'toc-item' : '';
                    if (heading.tagName.toLowerCase() === 'h3' && isSidebar) a.style.paddingRight = '20px';

                    a.onclick = (e) => {
                        e.preventDefault();
                        heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        if (isSidebar) {
                            tocList.querySelectorAll('a').forEach(el => el.classList.remove('active'));
                            a.classList.add('active');
                        }
                    };
                    li.appendChild(a);
                    tocList.appendChild(li);
                });
                targetContainer.appendChild(tocList);
                if (isSidebar) tocPlaceholder.classList.remove('d-none');
                if (!isSidebar) {
                    tocPlaceholder.classList.add('toc-container');
                    tocPlaceholder.insertAdjacentHTML('afterbegin', '<div class="toc-title"><i data-lucide="list"></i> فهرست مطالب</div>');
                }
                if (window.lucide) window.lucide.createIcons({ attrs: { 'data-lucide': true }, root: tocPlaceholder });
            }
        });
    };

    // Authentication & Modal Logic
    const userMenuBtns = document.querySelectorAll('.user-menu-btn');
    const authModal = document.getElementById('auth-modal');
    const profileModal = document.getElementById('profile-modal');
    const followersModal = document.getElementById('followers-modal');
    const followingModal = document.getElementById('following-modal');
    const followersTrigger = document.getElementById('followers-trigger');
    const followingTrigger = document.getElementById('following-trigger');
    const closeButtons = document.querySelectorAll('.close-modal');
    const authTabs = document.getElementById('auth-tabs');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    let authState = window.__AUTH_STATE__ || { isLoggedIn: false, user: null, csrfToken: '' };

    const fetchWithCSRF = async (url, options = {}) => {
        const headers = options.headers || {};
        if (authState.csrfToken) {
            headers['X-CSRF-Token'] = authState.csrfToken;
        }
        return fetch(url, { ...options, headers });
    };

    const updateUIForAuth = () => {
        const userTexts = document.querySelectorAll('.user-menu-text');
        const userIcons = document.querySelectorAll('.user-menu-btn .icon-size-5');
        const userAvatars = document.querySelectorAll('.user-menu-btn .user-avatar-nav');

        if (authState.isLoggedIn && authState.user) {
            userTexts.forEach(text => text.textContent = authState.user.name);

            // Handle avatar in nav
            if (authState.user.avatar) {
                userIcons.forEach(icon => icon.classList.add('d-none'));
                userAvatars.forEach(avatar => {
                    avatar.src = authState.user.avatar;
                    avatar.classList.remove('d-none');
                });

                // For containers that don't have an avatar yet
                document.querySelectorAll('.user-menu-btn .radius-50').forEach(container => {
                    if (!container.querySelector('.user-avatar-nav')) {
                        const img = document.createElement('img');
                        img.src = authState.user.avatar;
                        img.className = 'w-full h-full object-cover radius-50 user-avatar-nav';
                        container.appendChild(img);
                    }
                });
            }

            const profileName = profileModal.querySelector('h4');
            const profileEmail = profileModal.querySelector('p');
            const profileAvatar = profileModal.querySelector('.profile-modal-avatar');

            if (profileName) profileName.textContent = authState.user.name;
            if (profileEmail) profileEmail.textContent = authState.user.email;
            if (profileAvatar && authState.user.avatar) {
                profileAvatar.innerHTML = `<img src="${authState.user.avatar}" class="w-full h-full object-cover radius-50">`;
            }
        } else {
            userTexts.forEach(text => text.textContent = 'ورود / عضویت');
            userIcons.forEach(icon => icon.classList.remove('d-none'));
            userAvatars.forEach(avatar => avatar.classList.add('d-none'));
        }
    };

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    };

    window.showAuthModal = (tab = 'login') => {
        openModal(authModal);
        if (authTabs) {
            const btn = authTabs.querySelector(`button[data-tab="${tab}"]`);
            if (btn) btn.click();
        }
    };

    const closeModal = () => {
        const modals = [authModal, profileModal, followersModal, followingModal];
        modals.forEach(modal => {
            if (modal && !modal.classList.contains('d-none')) {
                const content = modal.querySelector('.modal-content');
                modal.classList.add('closing');
                if (content) content.classList.add('closing');

                setTimeout(() => {
                    modal.classList.add('d-none');
                    modal.classList.remove('closing');
                    if (content) content.classList.remove('closing');
                    document.body.style.overflow = '';
                }, 300);
            }
        });
    };

    userMenuBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (authState.isLoggedIn) {
                openModal(profileModal);
            } else {
                openModal(authModal);
            }
        });
    });

    closeButtons.forEach(btn => btn.addEventListener('click', closeModal));

    [authModal, profileModal, followersModal, followingModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }
    });

    if (authTabs) {
        authTabs.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                authTabs.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (tab === 'login') {
                    loginForm.classList.remove('d-none');
                    registerForm.classList.add('d-none');
                } else {
                    loginForm.classList.add('d-none');
                    registerForm.classList.remove('d-none');
                }
            });
        });
    }

    // Real Auth Handlers
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(loginForm);
            const email = convertDigitsToEnglish(formData.get('email'));
            const password = formData.get('password');

            const submitBtn = loginForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'در حال ورود...';

            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/auth.php?action=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await response.json();
                if (data.success) {
                    authState.isLoggedIn = true;
                    authState.user = data.user;
                    updateUIForAuth();
                    closeModal();
                    showAlert('با موفقیت وارد شدید', 'success');
                } else {
                    showAlert(data.message || 'خطا در ورود', 'error');
                }
            } catch (err) {
                showAlert('خطا در ارتباط با سرور', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'ورود به حساب';
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(registerForm);
            const name = formData.get('name');
            const email = formData.get('email');
            const phone = convertDigitsToEnglish(formData.get('phone'));
            const password = formData.get('password');

            const submitBtn = registerForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'در حال ثبت نام...';

            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/auth.php?action=register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, phone, password })
                });
                const data = await response.json();
                if (data.success) {
                    authState.isLoggedIn = true;
                    authState.user = data.user;
                    updateUIForAuth();
                    closeModal();
                    await showAlert('ثبت نام با موفقیت انجام شد. لطفاً ایمیل خود را چک کنید.', 'success');
                    fetch(`${authState.apiBase}/mail_worker.php`).catch(() => {});
                    location.href = '/profile';
                } else {
                    showAlert(data.message || 'خطا در ثبت نام', 'error');
                }
            } catch (err) {
                showAlert('خطا در ارتباط با سرور', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'ایجاد حساب کاربری';
            }
        });
    }

    const googleBtn = document.querySelector('#auth-modal .btn-secondary');
    if (googleBtn) {
        if (authState.googleLoginEnabled) {
            googleBtn.addEventListener('click', () => {
                window.location.href = `${authState.apiBase}/google_auth.php?action=login`;
            });
        } else {
            googleBtn.classList.add('d-none');
            const divider = googleBtn.previousElementSibling;
            if (divider && divider.classList.contains('auth-divider')) {
                divider.classList.add('d-none');
            }
        }
    }

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                await fetchWithCSRF(`${authState.apiBase}/auth.php?action=logout`, { method: 'POST' });
                authState.isLoggedIn = false;
                authState.user = null;
                closeModal();
                await showAlert('از حساب خارج شدید', 'info');
                location.reload();
            } catch (err) {
                showAlert('خطا در خروج', 'error');
            }
        });
    }

    updateUIForAuth();
    enhanceContent();

    // Following System
    const followBtn = document.getElementById('follow-btn');
    if (followBtn) {
        followBtn.addEventListener('click', async () => {
            const userId = followBtn.dataset.userId;
            followBtn.disabled = true;
            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/profile.php?action=toggle_follow`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                const result = await response.json();
                if (result.success) {
                    const icon = followBtn.querySelector('i');
                    const text = followBtn.querySelector('span');
                    const countEl = document.getElementById('follower-count');

                    if (result.following) {
                        followBtn.classList.replace('btn-primary', 'btn-secondary');
                        text.textContent = 'لغو دنبال کردن';
                        icon.setAttribute('data-lucide', 'user-minus');
                    } else {
                        followBtn.classList.replace('btn-secondary', 'btn-primary');
                        text.textContent = 'دنبال کردن';
                        icon.setAttribute('data-lucide', 'user-plus');
                    }
                    if (window.lucide) window.lucide.createIcons({ root: followBtn });
                    if (countEl) countEl.textContent = toPersianDigits(result.count);
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (err) {
                showAlert('خطا در ارتباط با سرور', 'error');
            } finally {
                followBtn.disabled = false;
            }
        });
    }

    const loadUserList = async (action, username, containerId) => {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<div class="text-center py-8"><i data-lucide="loader-2" class="spin text-primary"></i></div>';
        if (window.lucide) window.lucide.createIcons({ root: container });

        try {
            const response = await fetch(`${authState.apiBase}/profile.php?action=${action}&username=${username}`);
            const data = await response.json();
            if (data.success) {
                if (data.users.length === 0) {
                    container.innerHTML = '<p class="text-center text-gray py-4">لیست خالی است.</p>';
                } else {
                    container.innerHTML = data.users.map(u => `
                        <a href="/profile/${u.username}" class="user-row">
                            <img src="${u.avatar || '/assets/images/default-avatar.png'}" alt="${u.name}" onerror="this.src='/assets/images/default-avatar.png'">
                            <div class="grow-1">
                                <div class="font-bold text-title">${u.name}</div>
                                <div class="text-gray font-size-0-8">@${u.username}</div>
                            </div>
                            <i data-lucide="chevron-left" class="text-gray icon-size-4"></i>
                        </a>
                    `).join('');
                    if (window.lucide) window.lucide.createIcons({ root: container });
                }
            } else {
                container.innerHTML = `<p class="text-center text-error py-4">${data.message}</p>`;
            }
        } catch (err) {
            container.innerHTML = '<p class="text-center text-error py-4">خطا در بارگذاری لیست</p>';
        }
    };

    if (followersTrigger) {
        followersTrigger.onclick = () => {
            const pathParts = window.location.pathname.split('/').filter(p => p !== '');
            const username = pathParts[pathParts.length - 1];
            openModal(followersModal);
            loadUserList('get_followers', username, 'followers-list');
        };
    }

    if (followingTrigger) {
        followingTrigger.onclick = () => {
            const pathParts = window.location.pathname.split('/').filter(p => p !== '');
            const username = pathParts[pathParts.length - 1];
            openModal(followingModal);
            loadUserList('get_following', username, 'following-list');
        };
    }

    // Profile Page Logic
    const profileTabsContainer = document.getElementById('profile-tabs');
    if (profileTabsContainer) {
        const tabButtons = profileTabsContainer.querySelectorAll('.profile-tab-btn[data-tab]');
        const tabContents = document.querySelectorAll('.profile-tab-content');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;

                // Update active button
                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Show target content
                tabContents.forEach(content => {
                    if (content.id === `tab-${targetTab}`) {
                        content.classList.remove('d-none');
                    } else {
                        content.classList.add('d-none');
                    }
                });

                // Update URL for persistence
                const url = new URL(window.location);
                url.searchParams.set('tab', targetTab);
                window.history.pushState({}, '', url);
            });
        });

        // Handle initial tab from URL
        const urlParams = new URLSearchParams(window.location.search);
        const initialTab = urlParams.get('tab');
        if (initialTab) {
            const initialBtn = Array.from(tabButtons).find(b => b.dataset.tab === initialTab);
            if (initialBtn) initialBtn.click();
        }
    }

    const profileUpdateForm = document.getElementById('profile-update-form');
    if (profileUpdateForm) {
        profileUpdateForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(profileUpdateForm);
            const data = Object.fromEntries(formData.entries());
            if (data.phone) data.phone = convertDigitsToEnglish(data.phone);
            const submitBtn = profileUpdateForm.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.textContent = 'در حال ذخیره...';

            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/profile.php?action=update_info`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    await showAlert(result.message, 'success');
                    location.reload(); // Reload to update all UI parts
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (err) {
                showAlert('خطا در ارتباط با سرور', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'ذخیره تغییرات';
            }
        });
    }

    const passwordUpdateForm = document.getElementById('password-update-form');
    if (passwordUpdateForm) {
        passwordUpdateForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(passwordUpdateForm);
            const data = Object.fromEntries(formData.entries());
            const submitBtn = passwordUpdateForm.querySelector('button[type="submit"]');

            if (data.new_password !== data.confirm_password) {
                showAlert('رمز عبور جدید و تکرار آن مطابقت ندارند.', 'warning');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'در حال بروزرسانی...';

            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/profile.php?action=change_password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    showAlert(result.message, 'success');
                    passwordUpdateForm.reset();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (err) {
                showAlert('خطا در ارتباط با سرور', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'بروزرسانی رمز عبور';
            }
        });
    }

    const profileLogoutBtn = document.getElementById('profile-logout-btn');
    if (profileLogoutBtn) {
        profileLogoutBtn.addEventListener('click', async () => {
             if (await showConfirm('آیا از خروج اطمینان دارید؟')) {
                 await fetchWithCSRF(`${authState.apiBase}/auth.php?action=logout`, { method: 'POST' });
                 location.href = '/';
             }
        });
    }

    const verifyPhoneBtn = document.getElementById('verify-phone-btn');
    const resendSmsBtn = document.getElementById('resend-sms-btn');
    const phoneCodeInput = document.getElementById('phone-verification-code');

    if (verifyPhoneBtn) {
        verifyPhoneBtn.addEventListener('click', async () => {
            const code = convertDigitsToEnglish(phoneCodeInput.value.trim());
            if (!code) {
                showAlert('لطفاً کد تایید را وارد کنید.', 'warning');
                return;
            }

            verifyPhoneBtn.disabled = true;
            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/profile.php?action=verify_phone`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code })
                });
                const result = await response.json();
                if (result.success) {
                    await showAlert(result.message, 'success');
                    location.reload();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (err) {
                showAlert('خطا در تایید کد', 'error');
            } finally {
                verifyPhoneBtn.disabled = false;
            }
        });
    }

    if (resendSmsBtn) {
        resendSmsBtn.addEventListener('click', async () => {
            resendSmsBtn.disabled = true;
            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/profile.php?action=send_phone_verification`, {
                    method: 'POST'
                });
                const result = await response.json();
                if (result.success) {
                    showAlert(result.message, 'success');
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (err) {
                showAlert('خطا در ارسال پیامک', 'error');
            } finally {
                resendSmsBtn.disabled = false;
            }
        });
    }

    const resendVerificationBtn = document.getElementById('resend-verification-btn');
    if (resendVerificationBtn) {
        resendVerificationBtn.addEventListener('click', async () => {
            resendVerificationBtn.disabled = true;
            const originalText = resendVerificationBtn.innerHTML;
            resendVerificationBtn.innerHTML = '<i data-lucide="loader-2" class="icon-size-3 spin"></i> در حال ارسال...';
            if (window.lucide) window.lucide.createIcons({ root: resendVerificationBtn });

            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/profile.php?action=resend_verification`, {
                    method: 'POST'
                });
                const result = await response.json();
                if (result.success) {
                    await showAlert(result.message, 'success');
                    // Trigger mail worker
                    fetch(`${authState.apiBase}/mail_worker.php`).catch(() => {});
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (err) {
                showAlert('خطا در ارتباط با سرور', 'error');
            } finally {
                resendVerificationBtn.disabled = false;
                resendVerificationBtn.innerHTML = originalText;
                if (window.lucide) window.lucide.createIcons({ root: resendVerificationBtn });
            }
        });
    }

    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Validate client-side
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                showAlert('فرمت فایل باید JPG, PNG یا WEBP باشد.', 'warning');
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                showAlert('حجم فایل نباید بیشتر از ۲ مگابایت باشد.', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const response = await fetchWithCSRF(`${authState.apiBase}/profile.php?action=update_avatar`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    await showAlert(result.message, 'success');
                    location.reload();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (err) {
                showAlert('خطا در بارگذاری تصویر', 'error');
            }
        });
    }

    // PWA Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('SW registered: ', registration);
                })
                .catch(registrationError => {
                    console.log('SW registration failed: ', registrationError);
                });
        });
    }

    // Async parts
    (async () => {
        if (window.__INITIAL_STATE__ && window.__INITIAL_STATE__.platforms) {
            initSearch(window.__INITIAL_STATE__.platforms);
        } else {
            try {
                const response = await fetch(`${authState.apiBase}/dashboard.php`);
                const data = await response.json();
                if (data && data.platforms) initSearch(data.platforms);
            } catch (e) {}
        }
        if (window.lucide) window.lucide.createIcons();
        window.__APP_READY__ = true;
        document.dispatchEvent(new CustomEvent('app:content-ready'));

        // Trigger Mail Worker if on Admin or if requested (Basic async queue processing)
        if (window.location.pathname.includes('/admin/')) {
            fetch(`${authState.apiBase}/mail_worker.php`).catch(() => {});
        }
    })();
});
