const { test, expect } = require('@playwright/test');

test('Verify SEO and Performance Optimizations', async ({ page }) => {
  await page.goto('http://localhost:8000');

  console.log('--- Page Title and Meta ---');
  const title = await page.title();
  console.log('Title:', title);
  const description = await page.getAttribute('meta[name="description"]', 'content');
  console.log('Description:', description);
  const canonical = await page.getAttribute('link[rel="canonical"]', 'href');
  console.log('Canonical:', canonical);

  console.log('\n--- Heading Hierarchy ---');
  const h1s = await page.$$eval('h1', nodes => nodes.map(n => n.innerText));
  console.log('H1s:', h1s);

  console.log('\n--- Performance (Critical Path) ---');
  const criticalStyles = await page.$$eval('style', nodes => nodes.length);
  console.log('Inlined styles count:', criticalStyles);

  console.log('\n--- Image Optimization ---');
  const images = await page.$$eval('img', nodes => nodes.map(n => ({
    src: n.src,
    alt: n.alt,
    width: n.getAttribute('width'),
    height: n.getAttribute('height'),
    loading: n.getAttribute('loading')
  })));
  images.forEach(img => {
    console.log(`Image src: ${img.src}, alt: "${img.alt}", size: ${img.width}x${img.height}, loading: ${img.loading}`);
  });

  console.log('\n--- Accessibility ---');
  const ariaLabels = await page.$$eval('[aria-label]', nodes => nodes.map(n => n.getAttribute('aria-label')));
  console.log('Aria labels found:', ariaLabels.length);

  // Check for ApexCharts lazy loading
  const scripts = await page.$$eval('script[src]', nodes => nodes.map(n => n.src));
  const hasApex = scripts.some(s => s.includes('apexcharts'));
  console.log('ApexCharts script present in DOM:', hasApex);

  await page.screenshot({ path: 'seo_verification.png', fullPage: true });
});
