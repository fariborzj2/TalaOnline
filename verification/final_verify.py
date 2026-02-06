from playwright.sync_api import sync_playwright
import os
import time

def verify_ui():
    with sync_playwright() as p:
        # Launch browser
        browser = p.chromium.launch(headless=True)

        # Helper to take screenshot
        def capture(name, viewport, is_dark=False):
            context = browser.new_context(viewport=viewport)
            page = context.new_page()
            page.goto("http://localhost:8000")

            # Wait for data to load
            page.wait_for_selector(".coin-item", timeout=5000)
            time.sleep(1) # Allow animations to settle

            if is_dark:
                # Toggle to dark mode if not already
                current_theme = page.evaluate("document.documentElement.getAttribute('data-theme')")
                if current_theme != 'dark':
                    page.click("#theme-toggle")
                    time.sleep(0.5)

            os.makedirs("verification", exist_ok=True)
            page.screenshot(path=f"verification/{name}.png", full_page=True)
            context.close()

        # Desktop View (1440x900)
        capture("desktop_light", {"width": 1440, "height": 900}, is_dark=False)
        capture("desktop_dark", {"width": 1440, "height": 900}, is_dark=True)

        # Mobile View (iPhone 13 Pro Max)
        capture("mobile_light", {"width": 428, "height": 926}, is_dark=False)
        capture("mobile_dark", {"width": 428, "height": 926}, is_dark=True)

        browser.close()

if __name__ == "__main__":
    verify_ui()
