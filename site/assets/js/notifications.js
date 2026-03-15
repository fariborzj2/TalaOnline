document.addEventListener('DOMContentLoaded', function() {
    let authState = window.__AUTH_STATE__ || { isLoggedIn: false, user: null, csrfToken: '' };

    const notificationBtns = [
        document.getElementById('notification-btn-desktop'),
        document.getElementById('notification-btn-mobile')
    ];
    const notificationDropdowns = [
        document.getElementById('notification-dropdown-desktop'),
        document.getElementById('notification-dropdown-mobile')
    ];
    const notificationBadges = [
        document.getElementById('notification-badge-desktop'),
        document.getElementById('notification-badge-mobile')
    ];
    const notificationLists = [
        document.getElementById('notification-list-desktop'),
        document.getElementById('notification-list-mobile')
    ];
    const markAllBtns = [
        document.getElementById('mark-all-read-desktop'),
        document.getElementById('mark-all-read-mobile')
    ];

    document.addEventListener('auth:csrf-updated', (e) => {
        authState.csrfToken = e.detail;
    });

    const fetchWithCSRF = async (url, options = {}) => {
        const headers = options.headers || {};
        if (authState.csrfToken) {
            headers['X-CSRF-Token'] = authState.csrfToken;
        }
        return fetch(url, { ...options, headers });
    };

    const toPersianDigits = (num) => {
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return num.toString().replace(/\d/g, x => persian[x]);
    };

    const loadNotifications = async () => {
        if (!authState.isLoggedIn) return;

        try {
            const response = await fetch(`${authState.apiBase}/notifications.php?action=list`);
            const data = await response.json();

            if (data.success) {
                updateNotificationUI(data.notifications, data.unread_count);
            }
        } catch (err) {
            console.error('Error loading notifications:', err);
        }
    };

    const updateNotificationUI = (notifications, unreadCount) => {
        // Update badges
        notificationBadges.forEach(badge => {
            if (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 9 ? '+۹' : toPersianDigits(unreadCount);
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            }
        });

        // Update lists
        notificationLists.forEach(list => {
            if (!list) return;

            if (notifications.length === 0) {
                list.innerHTML = '<div class="text-center py-4 text-gray">اعلان جدیدی ندارید</div>';
                return;
            }

            list.innerHTML = notifications.map(n => {
                let text = '';
                let icon = 'bell';

                if (n.type === 'mention') {
                    text = `<strong>${n.sender_name}</strong> از شما در یک نظر نام برد.`;
                    icon = 'at-sign';
                } else if (n.type === 'reply') {
                    text = `<strong>${n.sender_name}</strong> به نظر شما پاسخ داد.`;
                    icon = 'message-square';
                } else if (n.type === 'follow') {
                    text = `<strong>${n.sender_name}</strong> شما را دنبال کرد.`;
                    icon = 'user-plus';
                } else if (n.custom_title || n.custom_body) {
                    text = n.custom_title ? `<strong>${n.custom_title}</strong><br><span class="text-gray text-xs mt-1 block">${n.custom_body}</span>` : n.custom_body;
                    icon = n.custom_icon || 'bell';
                }

                const url = n.target_info ? n.target_info.url : '#';
                const unreadClass = n.is_read == 0 ? 'unread' : '';

                return `
                    <a href="${url}" class="notification-item ${unreadClass}" data-id="${n.id}">
                        <div class="notification-item-avatar">
                            ${n.sender_avatar ? `<img src="${n.sender_avatar}" alt="${n.sender_name}">` : `<i data-lucide="${icon}" class="icon-size-4 text-gray"></i>`}
                        </div>
                        <div class="notification-item-content">
                            <div class="notification-item-text">${text}</div>
                            <div class="notification-item-time">${n.created_at_fa}</div>
                        </div>
                    </a>
                `;
            }).join('');

            if (window.lucide) window.lucide.createIcons({ root: list });

            // Add click handlers for individual notifications
            list.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', async (e) => {
                    const id = item.dataset.id;
                    const url = item.getAttribute('href');

                    if (item.classList.contains('unread')) {
                        e.preventDefault();
                        await markAsRead(id);
                        window.location.href = url;
                    }
                });
            });
        });
    };

    const markAsRead = async (id = null) => {
        try {
            const response = await fetchWithCSRF(`${authState.apiBase}/notifications.php?action=mark_read`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await response.json();
            if (data.success) {
                loadNotifications();
            }
        } catch (err) {
            console.error('Error marking as read:', err);
        }
    };

    // Toggle dropdown
    notificationBtns.forEach((btn, index) => {
        if (!btn) return;
        btn.addEventListener('click', (e) => {
            if (!authState.isLoggedIn) {
                if (window.showAuthModal) window.showAuthModal('login');
                return;
            }

            // Close other dropdowns
            notificationDropdowns.forEach((d, i) => {
                if (i !== index && d) d.classList.remove('active');
            });

            const dropdown = notificationDropdowns[index];
            if (dropdown) {
                dropdown.classList.toggle('active');
                if (dropdown.classList.contains('active')) {
                    loadNotifications();
                }
            }
            e.stopPropagation();
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', () => {
        notificationDropdowns.forEach(d => {
            if (d) d.classList.remove('active');
        });
    });

    notificationDropdowns.forEach(d => {
        if (d) d.addEventListener('click', (e) => e.stopPropagation());
    });

    // Mark all as read
    markAllBtns.forEach(btn => {
        if (!btn) return;
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            await markAsRead();
        });
    });

    // Listen for auth changes
    document.addEventListener('auth:status-changed', (e) => {
        authState = e.detail;
        if (authState.isLoggedIn) {
            loadNotifications();
        } else {
            updateNotificationUI([], 0);
        }
    });

    document.addEventListener('notifications:marked-read', () => {
        loadNotifications();
    });

    // Initial load
    if (authState.isLoggedIn) {
        loadNotifications();
        // Check every 2 minutes
        setInterval(loadNotifications, 120000);
    }
});
