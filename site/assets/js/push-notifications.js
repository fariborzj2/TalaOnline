class PushNotificationManager {
    constructor() {
        this.swRegistration = null;
        this.isSubscribed = false;
        this.applicationServerKey = window.__PUSH_CONFIG__?.publicKey;
    }

    async init() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Push notifications are not supported');
            return;
        }

        this.swRegistration = await navigator.serviceWorker.ready;
        this.isSubscribed = !!(await this.swRegistration.pushManager.getSubscription());

        this.updateUI();
    }

    updateUI() {
        const btn = document.getElementById('push-toggle-btn');
        if (btn) {
            btn.textContent = this.isSubscribed ? 'غیرفعال‌سازی پوش' : 'فعال‌سازی پوش';
            btn.classList.toggle('btn-success', this.isSubscribed);
            btn.classList.toggle('btn-primary', !this.isSubscribed);
        }
    }

    async subscribeUser() {
        if (!this.applicationServerKey) {
            console.error('Push public key not found');
            return;
        }

        const subscription = await this.swRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: this.urlB64ToUint8Array(this.applicationServerKey)
        });

        await this.sendSubscriptionToServer(subscription);
        this.isSubscribed = true;
        this.updateUI();

        if (window.showAlert) window.showAlert('اعلان‌های پوش با موفقیت فعال شد', 'success');
    }

    async unsubscribeUser() {
        const subscription = await this.swRegistration.pushManager.getSubscription();
        if (subscription) {
            await subscription.unsubscribe();
            await this.removeSubscriptionFromServer(subscription);
            this.isSubscribed = false;
            this.updateUI();
            if (window.showAlert) window.showAlert('اعلان‌های پوش غیرفعال شد', 'info');
        }
    }

    async sendSubscriptionToServer(subscription) {
        const response = await fetch('/api/push-notifications.php?action=subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscription)
        });
        return response.json();
    }

    async removeSubscriptionFromServer(subscription) {
        const response = await fetch('/api/push-notifications.php?action=unsubscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscription)
        });
        return response.json();
    }

    urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
}

window.pushManager = new PushNotificationManager();
document.addEventListener('DOMContentLoaded', () => {
    window.pushManager.init();

    const settingsForm = document.getElementById('notification-settings-form');
    if (settingsForm) {
        // Load initial settings
        fetch('/api/push-notifications.php?action=get_settings')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.settings) {
                    const s = data.settings;
                    const cats = JSON.parse(s.categories || '[]');
                    settingsForm.querySelectorAll('input[name="categories[]"]').forEach(cb => {
                        cb.checked = cats.includes(cb.value);
                    });

                    const channels = JSON.parse(s.channels || '["webpush", "email", "in-app"]');
                    settingsForm.querySelectorAll('input[name="channels[]"]').forEach(cb => {
                        cb.checked = channels.includes(cb.value);
                    });

                    settingsForm.querySelector('[name="frequency_limit"]').value = s.frequency_limit;
                    settingsForm.querySelector('[name="timezone"]').value = s.timezone;
                    if (s.quiet_hours_start) settingsForm.querySelector('[name="quiet_hours_start"]').value = s.quiet_hours_start;
                    if (s.quiet_hours_end) settingsForm.querySelector('[name="quiet_hours_end"]').value = s.quiet_hours_end;
                }
            });

        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(settingsForm);
            const data = {
                categories: formData.getAll('categories[]'),
                channels: formData.getAll('channels[]'),
                frequency_limit: formData.get('frequency_limit'),
                timezone: formData.get('timezone'),
                quiet_hours_start: formData.get('quiet_hours_start'),
                quiet_hours_end: formData.get('quiet_hours_end')
            };

            const response = await fetch('/api/push-notifications.php?action=save_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                if (window.showAlert) window.showAlert('تنظیمات با موفقیت ذخیره شد', 'success');
            }
        });
    }
});
