from playwright.sync_api import sync_playwright
import os

def run_verification():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            page.goto("http://localhost:8000")

            print("--- Page Title and Meta ---")
            print(f"Title: {page.title()}")

            description = page.locator('meta[name="description"]').get_attribute('content')
            print(f"Description: {description}")

            canonical = page.locator('link[rel="canonical"]').get_attribute('href')
            print(f"Canonical: {canonical}")

            print("\n--- Heading Hierarchy ---")
            h1s = page.locator('h1').all_inner_texts()
            print(f"H1s: {h1s}")

            print("\n--- Image Optimization ---")
            images = page.locator('img').all()
            for img in images:
                src = img.get_attribute('src')
                alt = img.get_attribute('alt')
                w = img.get_attribute('width')
                h = img.get_attribute('height')
                print(f"Image src: {src}, alt: '{alt}', size: {w}x{h}")

            print("\n--- Accessibility ---")
            arias = page.locator('[aria-label]').all()
            print(f"Aria labels found: {len(arias)}")

            os.makedirs('/home/jules/verification', exist_ok=True)
            page.screenshot(path="/home/jules/verification/seo_verification.png", full_page=True)
            print("\nScreenshot saved to /home/jules/verification/seo_verification.png")

        except Exception as e:
            print(f"Error during verification: {e}")
        finally:
            browser.close()

if __name__ == "__main__":
    run_verification()
