const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  await page.goto('http://localhost:8000/test_overlap.html');
  await page.waitForTimeout(1000);
  await page.screenshot({ path: 'test_overlap.png' });
  await browser.close();
})();
