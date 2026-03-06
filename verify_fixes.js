const { chromium } = require('playwright');
const path = require('path');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  // Go to profile page
  await page.goto('http://localhost:8000/profile/1/test-user');

  // 1. Check if target_info is visible in initial SSR
  const targetInfoInitial = await page.locator('.comment-item .d-inline-flex .text-primary').first();
  console.log('Initial target_info visible:', await targetInfoInitial.isVisible());

  // 2. Trigger a sort to force JS re-render
  await page.click('.sort-btn[data-sort="popular"]');
  await page.waitForTimeout(1000); // Wait for fetch and render

  // 3. Check if target_info is still visible in JS render
  const targetInfoJS = await page.locator('.comment-item .d-inline-flex .text-primary').first();
  console.log('JS Render target_info visible:', await targetInfoJS.isVisible());
  const targetText = await targetInfoJS.innerText();
  console.log('Target text:', targetText);

  // 4. Open thread modal
  await page.click('.view-thread-btn');
  await page.waitForSelector('.thread-modal-container', { state: 'visible' });

  // Check modal scroll/height
  const modalBox = await page.locator('.thread-modal-container');
  const boxHeight = await modalBox.evaluate(el => el.getBoundingClientRect().height);
  const viewportHeight = await page.evaluate(() => window.innerHeight);
  console.log(`Modal height: ${boxHeight}, Viewport: ${viewportHeight}, Ratio: ${boxHeight / viewportHeight}`);

  await page.screenshot({ path: '/home/jules/verification/profile_js_render_check.png' });

  // Check mobile view
  await page.setViewportSize({ width: 375, height: 667 });
  await page.waitForTimeout(500);
  await page.screenshot({ path: '/home/jules/verification/profile_mobile_thread.png' });

  await browser.close();
})();
