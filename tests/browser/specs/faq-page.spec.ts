import { test, expect, answerOf, category, questions, toggle, waitForView, EMPTY_PAGE, FAQ_PAGE } from './support';

// The FAQ page as a visitor sees it (README "Usage", "Ordering", "Client Assets"). Every spec starts
// as a fresh visitor (no CMS session), so each has its own view-count session.
test.use({ storageState: { cookies: [], origins: [] } });

test('shows the page categories in page order, each with its questions in category order', async ({ page }) => {
    const assets: string[] = [];
    page.on('response', (r) => {
        const m = r.url().match(/silverstripe-faq\/client\/src\/(css\/faq-accordion\.css|js\/faq-accordion\.js|js\/faq-view-tracker\.js)/);
        if (m) assets.push(`${r.status()} ${m[1]}`);
    });
    await page.goto(FAQ_PAGE);
    await expect(page.locator('.faq-intro .fqb-intro')).toHaveText('Questions and answers.');

    // Page order (dragged on the page's FAQ Categories tab): Shipping, Billing, Returns, although
    // the categories' own default sort is by Title. "Unused" is not on the page. (The admin drag
    // spec reorders Returns, so its questions are not checked here.)
    await expect(page.locator('.faq-category__title')).toHaveText(['Shipping', 'Billing', 'Returns']);
    // Billing was dragged (join SortOrder) against the questions' own order; Shipping never was,
    // so the questions' own order (creation order) decides there.
    expect(await questions(category(page, 'Billing'))).toEqual(['Can I get an invoice?', 'How do I pay?', 'How do I contact you?']);
    expect(await questions(category(page, 'Shipping'))).toEqual(['How long does delivery take?', 'Do you ship abroad?', 'How do I contact you?']);
    // Every answer starts hidden.
    await expect(page.locator('.faq-answer:visible')).toHaveCount(0);

    expect(assets.sort()).toEqual(['200 css/faq-accordion.css', '200 js/faq-accordion.js', '200 js/faq-view-tracker.js']);
});

test('a question opens and closes its answer, by click and by keyboard', async ({ page }) => {
    await page.goto(FAQ_PAGE);
    const button = toggle(category(page, 'Billing'), 'How do I pay?');
    const answer = await answerOf(page, button);
    await expect(button).toHaveAttribute('aria-expanded', 'false');
    await expect(answer).toBeHidden();

    await button.click();
    await expect(button).toHaveAttribute('aria-expanded', 'true');
    await expect(answer).toBeVisible();
    // The answer is HTML.
    await expect(answer.locator('strong')).toHaveText('bank transfer');

    await button.click();
    // Collapsing animates for 300 ms before the attributes flip.
    await expect(button).toHaveAttribute('aria-expanded', 'false');
    await expect(answer).toBeHidden();

    // Keyboard: Enter and Space on the focused button.
    await button.focus();
    await page.keyboard.press('Enter');
    await expect(answer).toBeVisible();
    await expect(button).toHaveAttribute('aria-expanded', 'true');
    await page.keyboard.press(' ');
    await expect(answer).toBeHidden();
});

test('a question in two categories has an answer per category, toggled independently', async ({ page }) => {
    await page.goto(FAQ_PAGE);
    const inShipping = toggle(category(page, 'Shipping'), 'How do I contact you?');
    const inBilling = toggle(category(page, 'Billing'), 'How do I contact you?');
    const a1 = await answerOf(page, inShipping);
    const a2 = await answerOf(page, inBilling);
    expect(await inShipping.getAttribute('aria-controls')).not.toBe(await inBilling.getAttribute('aria-controls'));

    await inBilling.click();
    await expect(a2).toBeVisible();
    await expect(a1).toBeHidden();
    await expect(inShipping).toHaveAttribute('aria-expanded', 'false');
});

test('opening a question counts one view per visitor session', async ({ page }) => {
    await page.goto(FAQ_PAGE);
    const button = toggle(category(page, 'Shipping'), 'Do you ship abroad?');
    const faqId = await button.getAttribute('data-faq-id');

    // First open: one POST with the question ID and the CSRF token, counted.
    const first = await waitForView(page, () => button.click());
    expect(first.post).toContain(`name="faqId"\r\n\r\n${faqId}`);
    expect(first.post).toContain(`name="SecurityID"\r\n\r\n${await button.getAttribute('data-security-token')}`);
    expect(first.json).toMatchObject({ success: true, alreadyCounted: false });
    const counted = first.json.viewCount;
    expect(counted).toBeGreaterThan(0);

    // Close and reopen on the same page load: the tracker does not ask again.
    const posts: string[] = [];
    page.on('request', (r) => r.url().includes('/faq-api/') && posts.push(r.url()));
    await button.click();
    await expect(button).toHaveAttribute('aria-expanded', 'false');
    await button.click();
    await expect(button).toHaveAttribute('aria-expanded', 'true');
    await page.waitForTimeout(300);
    expect(posts, 'no second request on the same page load').toEqual([]);

    // After a reload the tracker asks again, but the session already counted this question.
    await page.reload();
    const again = await waitForView(page, () => toggle(category(page, 'Shipping'), 'Do you ship abroad?').click());
    expect(again.json).toEqual({ success: true, viewCount: counted, alreadyCounted: true });
});

test('the tracker posts to the URL the template renders, not to a fixed path (#2)', async ({ page }) => {
    await page.goto(FAQ_PAGE);
    const button = toggle(category(page, 'Billing'), 'How do I pay?');
    // At the domain root the rendered URL is the root path: the scratch host is not in a subdirectory.
    await expect(button).toHaveAttribute('data-view-tracking-url', '/faq-api/incrementView');

    // A site in a subdirectory renders a different URL into the same attribute. Point it at a
    // marked URL that still routes, and check the tracker uses it instead of a path of its own.
    await button.evaluate((el) => el.setAttribute('data-view-tracking-url', '/faq-api/incrementView?from-attribute=1'));
    const [request] = await Promise.all([
        page.waitForRequest((r) => r.method() === 'POST' && r.url().includes('/faq-api/')),
        button.click(),
    ]);
    expect(new URL(request.url()).search).toBe('?from-attribute=1');
    expect((await request.response())?.status()).toBe(200);
});

test('the view API refuses a request without a valid token', async ({ page }) => {
    await page.goto(FAQ_PAGE);
    const faqId = await toggle(category(page, 'Billing'), 'How do I pay?').getAttribute('data-faq-id');
    // Through the page's own request context (same cookies, same session), so the expected 403
    // is not logged as a failed resource in the page console.
    const res = await page.request.post('/faq-api/incrementView', {
        multipart: { faqId: String(faqId), SecurityID: 'not-the-token' },
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const result = { status: res.status(), json: await res.json() };
    expect(result).toEqual({ status: 403, json: { error: 'Invalid security token' } });
});

test('a page without categories says there are no FAQs yet', async ({ page }) => {
    await page.goto(EMPTY_PAGE);
    await expect(page.locator('.faq-empty')).toHaveText('No FAQs have been added to this page yet.');
    await expect(page.locator('.faq-category')).toHaveCount(0);
});
