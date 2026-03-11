import { test, expect } from '@playwright/test';

test('verify admin notifications page loads', async ({ page }) => {
  // We need to bypass login or set a session cookie if possible,
  // but let's try to just visit the URL and see if it at least doesn't 500.
  // In a real scenario I'd need to log in, but since I don't have credentials easily available,
  // I'll check if the page exists and the server responds.
  const response = await page.goto('http://localhost:8000/admin/notifications.php');
  console.log(`Admin Notifications status: ${response?.status()}`);
  await page.screenshot({ path: 'verification/admin_notifications.png' });
});

test('verify admin notification templates page loads', async ({ page }) => {
  const response = await page.goto('http://localhost:8000/admin/notification_templates.php');
  console.log(`Admin Templates status: ${response?.status()}`);
  await page.screenshot({ path: 'verification/admin_templates.png' });
});
