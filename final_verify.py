import asyncio
from playwright.async_api import async_playwright

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        context = await browser.new_context(viewport={'width': 1280, 'height': 2000})
        page = await context.new_page()

        # Mock auth state
        await page.add_init_script("""
            window.__AUTH_STATE__ = {
                isLoggedIn: true,
                user: { id: 1, username: 'admin', role: 'admin' },
                csrfToken: 'test-token'
            };
        """)

        async def handle(route):
            response = await route.fetch()
            json_data = await response.json()
            if json_data.get('comments') and len(json_data['comments']) > 0:
                json_data['comments'][0]['can_edit'] = True
                json_data['comments'][0]['user_level'] = 5
            await route.fulfill(json=json_data)

        # Mock API response
        await page.route('**/api/comments.php?action=list*', handle)

        print("Final verification of asset page with mocked API...")
        await page.goto('http://localhost:8000/gold/18-karat-gold')
        await page.wait_for_selector('.sentiment-bar-container')
        await page.evaluate("document.querySelector('#comments-app').scrollIntoView()")
        await asyncio.sleep(3)

        # Check for specific elements
        level_badge = await page.query_selector('.user-level-badge')
        if level_badge:
            print("Level badge found!")

        edit_btn = await page.query_selector('.edit-btn')
        if edit_btn:
            print("Edit button found!")

        report_btn = await page.query_selector('.report-btn')
        if report_btn:
            print("Report button found!")

        # Take screenshot
        await page.screenshot(path='/home/jules/verification/final_comment_ui_mocked.png')

        await browser.close()

if __name__ == "__main__":
    asyncio.run(run())
