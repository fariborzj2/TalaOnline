# Push Notification System - Operational Documentation

## 1. Overview
The platform now features a fully autonomous, production-ready push notification system. It supports multi-channel delivery (Web Push, Email, In-App), priority-based queuing, and behavioral triggers.

## 2. Architecture Components

### Backend Engine
- **PushService (`includes/push_service.php`)**: The core delivery engine. Manages subscriptions, queues notifications, and handles multi-channel sending.
- **TriggerEngine (`includes/trigger_engine.php`)**: Maps application events (price spikes, new posts, user interactions) to specific notification templates.
- **Worker (`bin/notification_worker.php`)**: A background process that consumes the `notification_queue` and executes delivery logic.

### Frontend Integration
- **Service Worker (`site/sw.js`)**: Handles background `push` events and displays system notifications.
- **PushManager (`site/assets/js/push-notifications.js`)**: Manages VAPID subscriptions and user notification preferences.

### Admin & User Control
- **Admin Settings (`site/admin/notifications.php`)**: Global toggle, VAPID key management, and delivery analytics.
- **Template Manager (`site/admin/notification_templates.php`)**: CRUD for notification content and channel routing.
- **User Preferences**: A new "Notifications" tab in the user profile allows granular control over categories and frequency limits.

---

## 3. Database Schema
The system introduces 6 new tables:
- `push_subscriptions`: Stores VAPID endpoints per user.
- `notification_settings`: User-specific preferences (categories, frequency, timezone).
- `notification_templates`: Pre-defined content for various scenarios.
- `notification_queue`: High-performance delivery buffer.
- `notification_analytics`: Tracks sent, delivered, and clicked events.
- `email_queue`: Specifically for high-reliability email delivery.

---

## 4. Deployment Instructions

### Step 1: Initialize Database
Run the migration system by visiting any page or executing:
```bash
php bin/migrate_runner.php
```

### Step 2: Configure VAPID Keys
1. Navigate to **Admin > System > Notification Settings**.
2. Generate or paste your VAPID Public/Private keys.
3. Ensure `webpush_enabled` is set to `1`.

### Step 3: Start the Worker
The notification worker should run as a persistent service (e.g., via `systemd` or `supervisor`):
```bash
php bin/notification_worker.php
```

### Step 4: Verify Service Worker
Ensure `site/sw.js` is accessible at the domain root. Users will be prompted to enable notifications on their first visit or via their profile settings.

---

## 5. Automated Triggers
The following scenarios are currently live:
- **Market Volatility**: Alerts when an asset changes >5% in price.
- **Social Interaction**: Notifies users of mentions or replies in comments.
- **Content Updates**: Alerts users to new blog posts in their preferred categories.
- **Predictive Re-hook**: Automatically triggers a "Market Recap" for users inactive for 3+ days.
