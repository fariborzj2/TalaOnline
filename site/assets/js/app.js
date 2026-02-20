document.addEventListener('DOMContentLoaded', function() {
    const persianNumberFormatter = new Intl.NumberFormat('fa-IR');
    const toPersianDigits = (num) => {
        if (num === null || num === undefined) return '';
        return persianNumberFormatter.format(num);
    };

    const formatPrice = (price) => toPersianDigits(price);

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
    const userMenuBtn = document.getElementById('user-menu-btn');
    const authModal = document.getElementById('auth-modal');
    const profileModal = document.getElementById('profile-modal');
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
        const userText = document.getElementById('user-menu-text');
        const userIcon = document.querySelector('#user-menu-btn .icon-size-5');
        const userAvatar = document.querySelector('#user-menu-btn .user-avatar-nav');

        if (authState.isLoggedIn && authState.user) {
            if (userText) userText.textContent = authState.user.name;

            // Handle avatar in nav
            if (authState.user.avatar) {
                if (userIcon) userIcon.classList.add('d-none');
                if (userAvatar) {
                    userAvatar.src = authState.user.avatar;
                    userAvatar.classList.remove('d-none');
                } else {
                    const img = document.createElement('img');
                    img.src = authState.user.avatar;
                    img.className = 'w-full h-full object-cover radius-50 user-avatar-nav';
                    const container = document.querySelector('#user-menu-btn .radius-50');
                    if (container) container.appendChild(img);
                }
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
            if (userText) userText.textContent = 'ورود / عضویت';
            if (userIcon) userIcon.classList.remove('d-none');
            if (userAvatar) userAvatar.classList.add('d-none');
        }
    };

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        if (authModal) authModal.classList.add('d-none');
        if (profileModal) profileModal.classList.add('d-none');
        document.body.style.overflow = '';
    };

    if (userMenuBtn) {
        userMenuBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (authState.isLoggedIn) {
                openModal(profileModal);
            } else {
                openModal(authModal);
            }
        });
    }

    closeButtons.forEach(btn => btn.addEventListener('click', closeModal));

    [authModal, profileModal].forEach(modal => {
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
            const email = formData.get('email');
            const password = formData.get('password');

            const submitBtn = loginForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'در حال ورود...';

            try {
                const response = await fetchWithCSRF('/api/auth.php?action=login', {
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
                    alert('با موفقیت وارد شدید');
                } else {
                    alert(data.message || 'خطا در ورود');
                }
            } catch (err) {
                alert('خطا در ارتباط با سرور');
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
            const phone = formData.get('phone');
            const password = formData.get('password');

            const submitBtn = registerForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'در حال ثبت نام...';

            try {
                const response = await fetchWithCSRF('/api/auth.php?action=register', {
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
                    alert('ثبت نام با موفقیت انجام شد');
                } else {
                    alert(data.message || 'خطا در ثبت نام');
                }
            } catch (err) {
                alert('خطا در ارتباط با سرور');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'ایجاد حساب کاربری';
            }
        });
    }

    const googleBtn = document.querySelector('#auth-modal .btn-secondary');
    if (googleBtn) {
        googleBtn.addEventListener('click', () => {
            alert('در حال اتصال به گوگل... (شبیه‌سازی)');
            setTimeout(() => {
                authState.isLoggedIn = true;
                authState.user = { name: 'کاربر گوگل', email: 'google-user@gmail.com' };
                updateUIForAuth();
                closeModal();
                alert('با موفقیت از طریق گوگل وارد شدید');
            }, 1500);
        });
    }

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                await fetchWithCSRF('/api/auth.php?action=logout', { method: 'POST' });
                authState.isLoggedIn = false;
                authState.user = null;
                closeModal();
                alert('از حساب خارج شدید');
                location.reload();
            } catch (err) {
                alert('خطا در خروج');
            }
        });
    }

    updateUIForAuth();
    enhanceContent();

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
            const submitBtn = profileUpdateForm.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.textContent = 'در حال ذخیره...';

            try {
                const response = await fetchWithCSRF('/api/profile.php?action=update_info', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    location.reload(); // Reload to update all UI parts
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('خطا در ارتباط با سرور');
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
                alert('رمز عبور جدید و تکرار آن مطابقت ندارند.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'در حال بروزرسانی...';

            try {
                const response = await fetchWithCSRF('/api/profile.php?action=change_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    passwordUpdateForm.reset();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('خطا در ارتباط با سرور');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'بروزرسانی رمز عبور';
            }
        });
    }

    const profileLogoutBtn = document.getElementById('profile-logout-btn');
    if (profileLogoutBtn) {
        profileLogoutBtn.addEventListener('click', async () => {
             if (confirm('آیا از خروج اطمینان دارید؟')) {
                 await fetchWithCSRF('/api/auth.php?action=logout', { method: 'POST' });
                 location.href = '/';
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
                alert('فرمت فایل باید JPG, PNG یا WEBP باشد.');
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('حجم فایل نباید بیشتر از ۲ مگابایت باشد.');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const response = await fetchWithCSRF('/api/profile.php?action=update_avatar', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('خطا در بارگذاری تصویر');
            }
        });
    }

    // Async parts
    (async () => {
        if (window.__INITIAL_STATE__ && window.__INITIAL_STATE__.platforms) {
            initSearch(window.__INITIAL_STATE__.platforms);
        } else {
            try {
                const response = await fetch('/api/dashboard.php');
                const data = await response.json();
                if (data && data.platforms) initSearch(data.platforms);
            } catch (e) {}
        }
        if (window.lucide) window.lucide.createIcons();
        window.__APP_READY__ = true;
        document.dispatchEvent(new CustomEvent('app:content-ready'));
    })();
});
