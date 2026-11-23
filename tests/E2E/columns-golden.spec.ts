import { expect, test } from '@playwright/test';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const hashes = JSON.parse(
  readFileSync(path.join(root, 'fixtures/eds/vendor-hashes.json'), 'utf8'),
);

function assertVendorIntact(relative: string) {
  const expected = hashes.files[relative];
  const actual = createHash('sha256')
    .update(readFileSync(path.join(root, relative)))
    .digest('hex');
  expect(actual, `${relative} was modified`).toBe(expected);
}

test.describe('Columns Compatibility Golden Test', () => {
  test('loads original columns block without modifying vendor files', async ({
    page,
    request,
  }) => {
    assertVendorIntact('blocks/columns/columns.js');
    assertVendorIntact('blocks/columns/columns.css');
    assertVendorIntact('scripts/aem.js');

    const payload = JSON.parse(
      readFileSync(path.join(root, 'fixtures/eds/columns/page.json'), 'utf8'),
    );

    const create = await request.post('/api/v1/pages', { data: payload });
    expect(create.status()).toBe(201);
    const body = await create.json();
    const pageId = body.data.id as string;

    const consoleErrors: string[] = [];
    page.on('pageerror', (err) => consoleErrors.push(String(err)));
    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    const response = await page.goto(`/__preview/pages/${pageId}`, {
      waitUntil: 'networkidle',
    });
    expect(response?.status()).toBe(200);

    // Content present without relying on decoration classes alone
    await expect(page.locator('main')).toContainText('Left');
    await expect(page.locator('main')).toContainText('Right');

    // Runtime assets
    await expect(page.locator('script[src="/scripts/aem.js"]')).toHaveCount(1);
    await expect(page.locator('script[src="/scripts/scripts.js"]')).toHaveCount(1);

    const columnsBlock = page.locator('.columns.block');
    await expect(columnsBlock).toHaveAttribute('data-block-status', 'loaded', {
      timeout: 15000,
    });

    // Original decorate() adds columns-N-cols
    await expect(columnsBlock).toHaveClass(/columns-2-cols/);

    // CSS/JS requested
    const cssOk = await page.evaluate(async () => {
      const links = [...document.querySelectorAll('link[rel="stylesheet"]')].map(
        (l) => (l as HTMLLinkElement).href,
      );
      return links.some((href) => href.includes('/blocks/columns/columns.css'));
    });
    expect(cssOk).toBeTruthy();

    const jsLoaded = await page.evaluate(async () => {
      try {
        await import('/blocks/columns/columns.js');
        return true;
      } catch {
        return false;
      }
    });
    expect(jsLoaded).toBeTruthy();

    const unexpected = consoleErrors.filter(
      (e) =>
        !e.includes('fonts.css') &&
        !e.includes('Failed to load resource') &&
        !e.includes('net::ERR_'),
    );
    expect(unexpected, `JS console errors: ${unexpected.join('\n')}`).toEqual([]);
  });
});
