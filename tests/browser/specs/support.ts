import { test as base, expect, type Locator, type Page } from '@playwright/test';

// Shared fixtures and helpers for the FAQ specs.
//
// The front-end pages are the fixture page type in tests/browser/fixtures/ (copied into the scratch
// host by the runner): /faq-test shows the categories Shipping then Billing, /faq-empty has none.
// Every dev/build re-seeds all FAQ data with zero view counts (fixtures/FqBFaqPage.php).

export const FAQ_PAGE = '/faq-test';
export const EMPTY_PAGE = '/faq-empty';

/**
 * test, extended with an automatic console guard: every spec fails if the page logs a console
 * error or throws an uncaught exception at any point, page load included. faq-view-tracker.js
 * reports a failed tracking request with console.error, so a broken /faq-api call is caught too.
 */
export const test = base.extend<{ consoleGuard: void }>({
    consoleGuard: [
        async ({ page }, use, testInfo) => {
            const errors: string[] = [];
            page.on('console', (msg) => {
                if (msg.type() === 'error') {
                    errors.push(`console.error: ${msg.text()} (${msg.location().url})`);
                }
            });
            page.on('pageerror', (err) => errors.push(`uncaught: ${err.message}`));

            await use();

            if (errors.length) {
                await testInfo.attach('console-errors', { body: errors.join('\n'), contentType: 'text/plain' });
            }
            expect(errors, 'no console errors or uncaught exceptions').toEqual([]);
        },
        { auto: true },
    ],
});

export { expect };

/** A category block on the FAQ page, by its title. */
export function category(page: Page, title: string): Locator {
    return page.locator('.faq-category').filter({ has: page.locator('.faq-category__title', { hasText: new RegExp(`^\\s*${title}\\s*$`) }) });
}

/** The question texts of a category block, in page order. */
export async function questions(block: Locator): Promise<string[]> {
    return (await block.locator('.faq-toggle').allTextContents()).map((t) => t.trim());
}

/** The toggle button of a question within a block. */
export function toggle(block: Locator, question: string): Locator {
    return block.locator('.faq-toggle', { hasText: question });
}

/** The answer region a toggle controls (aria-controls). */
export async function answerOf(page: Page, button: Locator): Promise<Locator> {
    const id = await button.getAttribute('aria-controls');
    expect(id, 'the toggle names its answer').toBeTruthy();
    return page.locator(`#${id}`);
}

/** Wait for the view tracker's POST to /faq-api/incrementView and return its JSON reply. */
export async function waitForView(page: Page, action: () => Promise<void>): Promise<{ post: string; json: any }> {
    const [request] = await Promise.all([
        page.waitForRequest((r) => r.method() === 'POST' && new URL(r.url()).pathname === '/faq-api/incrementView'),
        action(),
    ]);
    const response = await request.response();
    expect(response?.status(), 'incrementView status').toBe(200);
    return { post: request.postData() ?? '', json: await response!.json() };
}
