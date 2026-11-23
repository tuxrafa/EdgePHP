import { expect, test } from '@playwright/test';

test('author can insert hero from library and preview loads decorate', async ({
  page,
}) => {
  await page.goto('/author/');
  await expect(page.getByTestId('login-form')).toBeVisible();
  await page.getByLabel('Email').fill('author@edgephp.local');
  await page.getByLabel('Senha').fill('author');
  await page.getByTestId('login-submit').click();
  await expect(page.locator('.brand')).toHaveText('EdgePHP');

  const stamp = Date.now();
  await page.getByRole('button', { name: 'Pages' }).click();
  await page.getByLabel('Nova página').fill(`Lib ${stamp}`);
  await page.getByLabel('Path').fill(`/lib-${stamp}`);
  await page.getByRole('button', { name: 'Criar página' }).click();

  await page.getByRole('button', { name: 'Blocks' }).click();
  await page.getByPlaceholder('hero, accordion, carrousel').fill('hero');
  await page.getByRole('button', { name: /^Hero/ }).click();
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByText('Salvo')).toBeVisible();

  const frame = page.frameLocator('iframe[title="EDS Preview"]');
  await expect(frame.locator('.hero.block')).toHaveAttribute(
    'data-block-status',
    'loaded',
    { timeout: 15000 },
  );
  await expect(frame.locator('.hero')).toContainText('Construa sites');
});
