const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1280, height: 800 });
  await page.goto('http://127.0.0.1:3000/index.html');

  // Wait for data to load
  await page.waitForSelector('tbody tr');

  // Take a screenshot of the table area
  const tableBox = await page.$('.table-box');
  if (tableBox) {
    // Hover over the table to ensure scrollbar might be visible if it has behavior on hover,
    // though here it should be always visible if overflow.
    await tableBox.hover();
    await tableBox.screenshot({ path: '/home/jules/verification/custom_scrollbar.png' });
  }

  await browser.close();
})();
