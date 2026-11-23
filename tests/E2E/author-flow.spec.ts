import { expect, test } from '@playwright/test';

test.describe('Author vertical flow', () => {
  test('login, create page, insert columns, save, preview loads EDS block', async ({
    page,
  }) => {
    const consoleErrors: string[] = [];
    page.on('pageerror', (err) => consoleErrors.push(String(err)));

    await page.goto('/author/');
    await expect(page.getByTestId('login-form')).toBeVisible();
    await page.getByLabel('Email').fill('author@edgephp.local');
    await page.getByLabel('Senha').fill('author');
    await page.getByTestId('login-submit').click();

    await expect(page.locator('.brand')).toHaveText('EdgePHP');

    const stamp = Date.now();
    await page.getByRole('button', { name: 'Pages' }).click();
    await page.getByLabel('Nova página').fill(`Home ${stamp}`);
    await page.getByLabel('Path').fill(`/home-${stamp}`);
    await page.getByRole('button', { name: 'Criar página' }).click();

    await expect(page.getByText(`/home-${stamp}`).first()).toBeVisible();

    await page.getByRole('button', { name: 'Blocks' }).click();
    await page.getByPlaceholder('hero, accordion, carrousel').fill('columns');
    await page.getByRole('button', { name: /^Columns/ }).click();

    const left = page.locator('.columns-grid textarea').nth(0);
    const right = page.locator('.columns-grid textarea').nth(1);
    await left.fill('Autor Left');
    await right.fill('Autor Right');

    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('Salvo')).toBeVisible();

    const frame = page.frameLocator('iframe[title="EDS Preview"]');
    await expect(frame.locator('main')).toContainText('Autor Left');
    await expect(frame.locator('.columns.block')).toHaveAttribute(
      'data-block-status',
      'loaded',
      { timeout: 15000 },
    );
    await expect(frame.locator('.columns.block')).toHaveClass(/columns-2-cols/);

    const unexpected = consoleErrors.filter(
      (e) => !e.includes('fonts.css') && !e.includes('Failed to load resource'),
    );
    expect(unexpected).toEqual([]);
  });
});
