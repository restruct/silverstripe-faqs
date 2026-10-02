import { test, expect, category, questions, toggle, waitForView, FAQ_PAGE } from './support';
import type { Browser, Locator, Page } from '@playwright/test';

// The FAQs admin (README "In the CMS"). These specs use the saved admin session.

const CATEGORIES = '/admin/faq/Restruct-FAQ-Model-FaqCategory';
const QUESTIONS = '/admin/faq/Restruct-FAQ-Model-FaqQuestion';

function rowTitled(grid: Locator, column: string, text: string): Locator {
    return grid.locator('tr.ss-gridfield-item').filter({ has: grid.page().locator(`td.col-${column}`, { hasText: new RegExp(`^\\s*${text.replace(/[?]/g, '\\?')}\\s*$`) }) });
}

/** Open the visitor's FAQ page in a fresh context (own session), run fn, close it. */
async function asVisitor<T>(browser: Browser, baseURL: string, fn: (page: Page) => Promise<T>): Promise<T> {
    // An explicit empty storageState: inside a test, browser.newContext() takes its defaults from
    // the project's `use`, which includes the saved admin session.
    const context = await browser.newContext({ baseURL, storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();
    const errors: string[] = [];
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
    try {
        await page.goto(FAQ_PAGE);
        const result = await fn(page);
        expect(errors, 'visitor page: no console errors').toEqual([]);
        return result;
    } finally {
        await context.close();
    }
}

test('the admin lists categories and questions, without export, print or import buttons', async ({ page }) => {
    await page.goto(CATEGORIES);
    const grid = page.locator('#Form_EditForm_Restruct-FAQ-Model-FaqCategory');
    await expect(grid.locator('td.col-Title')).toHaveText(['Billing', 'Returns', 'Shipping', 'Unused']);
    await expect(page.locator('.grid-print-button, .grid-field__export-button, [name="action_export"], .grid-field__import-button, .toolbar--content a:has-text("Import")')).toHaveCount(0);

    await page.locator('.cms-tabset-nav-primary a, .cms-content-header-tabs a', { hasText: /^FAQs$/ }).click();
    await expect(page).toHaveURL(new RegExp(`${QUESTIONS}$`));
    const qgrid = page.locator('#Form_EditForm_Restruct-FAQ-Model-FaqQuestion');
    await expect(qgrid.locator('tr.ss-gridfield-item')).toHaveCount(8);
    // CategoriesList: every category the question is in.
    await expect(rowTitled(qgrid, 'Question', 'How do I contact you?').locator('td.col-CategoriesList')).toHaveText('Billing, Shipping');
    await expect(page.locator('.grid-print-button, [name="action_export"]')).toHaveCount(0);
});

test('a view counted on the site shows in the admin', async ({ page, browser, baseURL }) => {
    await page.goto(QUESTIONS);
    const qgrid = page.locator('#Form_EditForm_Restruct-FAQ-Model-FaqQuestion');
    const cell = rowTitled(qgrid, 'Question', 'How long does delivery take?').locator('td.col-ViewCount');
    const before = Number((await cell.textContent())?.trim());

    await asVisitor(browser, baseURL!, async (visitor) => {
        const { json } = await waitForView(visitor, () => toggle(category(visitor, 'Shipping'), 'How long does delivery take?').click());
        expect(json).toMatchObject({ success: true, alreadyCounted: false, viewCount: before + 1 });
    });

    await page.reload();
    await expect(rowTitled(page.locator('#Form_EditForm_Restruct-FAQ-Model-FaqQuestion'), 'Question', 'How long does delivery take?').locator('td.col-ViewCount')).toHaveText(String(before + 1));
});

/** The questions of "Returns" in creation order, which is also their own SortOrder. */
const RETURNS_OWN_ORDER = ['Can I return an item?', 'Who pays for the return?', 'When do I get my money back?'];

/** Open the "Returns" category's FAQ questions GridField and return its rows. */
async function openReturnsQuestions(page: Page): Promise<Locator> {
    await page.goto(CATEGORIES);
    // "Returns" exists for these specs: no other spec reads its question order.
    await rowTitled(page.locator('#Form_EditForm_Restruct-FAQ-Model-FaqCategory'), 'Title', 'Returns').locator('td.col-Title').click();
    await expect(page.locator('#Form_ItemEditForm')).toBeVisible();
    await page.locator('a[href$="#Root_FAQQuestions"]').click();
    const rows = page.locator('#Form_ItemEditForm_Faqs tr.ss-gridfield-item');
    await expect(rows).toHaveCount(3);
    return rows;
}

async function questionOrder(rows: Locator): Promise<string[]> {
    return (await rows.locator('td.col-Question').allTextContents()).map((t) => t.trim());
}

/**
 * Drag one row's handle to the top or the bottom (GridFieldOrderableRows, jQuery UI sortable) and
 * wait for the reorder POST (.../field/Faqs/reorder), which saves straight away.
 */
async function dragRow(page: Page, rows: Locator, from: 'first' | 'last', to: 'top' | 'bottom'): Promise<void> {
    const reorder = page.waitForResponse((r) => r.request().method() === 'POST' && /\/field\/Faqs\/reorder/.test(r.url()));
    const handle = await (from === 'first' ? rows.first() : rows.last()).locator('.handle').boundingBox();
    const edge = await (to === 'top' ? rows.first() : rows.last()).boundingBox();
    const x = handle!.x + handle!.width / 2;
    await page.mouse.move(x, handle!.y + handle!.height / 2);
    await page.mouse.down();
    // Several small steps, so jQuery UI sees a real drag past its distance threshold, then past the
    // middle of the edge row, where the sortable moves the placeholder.
    await page.mouse.move(x, handle!.y + (to === 'top' ? -10 : 10), { steps: 5 });
    await page.mouse.move(x, to === 'top' ? edge!.y - edge!.height / 2 : edge!.y + edge!.height * 1.5, { steps: 10 });
    await page.mouse.up();
    expect((await reorder).status(), 'reorder POST').toBe(200);
}

const rotateDown = (o: string[]) => [o[2], o[0], o[1]]; // last row to the top
const rotateUp = (o: string[]) => [o[1], o[2], o[0]]; // first row to the bottom

test('dragging a question within a category reorders it on the FAQ page', async ({ page, browser, baseURL }) => {
    const rows = await openReturnsQuestions(page);
    const before = await questionOrder(rows);

    // Move the last row to the top, unless that would restore the questions' own order, which
    // does not save (#5, the fixme spec below); then move the first row to the bottom instead.
    // Either way the new order differs from the old one and from the own order, so the spec
    // holds under --repeat-each.
    const down = rotateDown(before);
    const useDown = down.join() !== RETURNS_OWN_ORDER.join();
    const expected = useDown ? down : rotateUp(before);
    await dragRow(page, rows, useDown ? 'last' : 'first', useDown ? 'top' : 'bottom');

    // The GridField re-rendered from the database in the new order.
    await expect(rows.locator('td.col-Question')).toHaveText(expected);
    // And the FAQ page follows the category's dragged order.
    const onPage = await asVisitor(browser, baseURL!, async (visitor) => questions(category(visitor, 'Returns')));
    expect(onPage).toEqual(expected);
});

test.fixme('dragging the questions back into creation order is saved (#5)', async ({ page, browser, baseURL }) => {
    // https://github.com/restruct/silverstripe-faqs/issues/5 - the join field SortOrder clashes
    // with FaqQuestion.SortOrder, so GridFieldOrderableRows compares the new positions with the
    // questions' own values and skips every row when the target order is the own order.
    let rows = await openReturnsQuestions(page);
    let current = await questionOrder(rows);
    if (current.join() === RETURNS_OWN_ORDER.join()) {
        await dragRow(page, rows, 'last', 'top');
        current = rotateDown(current);
        await expect(rows.locator('td.col-Question')).toHaveText(current);
    }
    // Two rotations of three rows: one of them restores the own order.
    const viaDown = rotateDown(current).join() === RETURNS_OWN_ORDER.join();
    rows = await openReturnsQuestions(page);
    await dragRow(page, rows, viaDown ? 'last' : 'first', viaDown ? 'top' : 'bottom');

    await expect(rows.locator('td.col-Question')).toHaveText(RETURNS_OWN_ORDER);
    const onPage = await asVisitor(browser, baseURL!, async (visitor) => questions(category(visitor, 'Returns')));
    expect(onPage).toEqual(RETURNS_OWN_ORDER);
});
