import { expect, test } from '@playwright/test';

test('create page from Article template and preview shows shared header', async ({
  page,
}) => {
  await page.goto('/author/');
  await page.getByLabel('Email').fill('author@edgephp.local');
  await page.getByLabel('Senha').fill('author');
  await page.getByTestId('login-submit').click();
  await expect(page.locator('.brand')).toHaveText('EdgePHP');

  await page.getByRole('button', { name: 'Pages' }).click();
  const stamp = Date.now();
  await page.getByLabel('Nova página').fill(`Article ${stamp}`);
  await page.getByLabel('Path').fill(`/blog/article-${stamp}`);

  const templateSelect = page.locator('#new-template');
  await expect(templateSelect.locator('option')).not.toHaveCount(1, {
    timeout: 10000,
  });
  const value = await templateSelect.locator('option').evaluateAll((opts) => {
    const hit = opts.find((o) => (o.textContent || '').includes('Article'));
    return hit ? (hit as HTMLOptionElement).value : '';
  });
  expect(value).toBeTruthy();
  await templateSelect.selectOption(value);

  await page.getByRole('button', { name: 'Criar página' }).click();
  await expect(page.getByText(/Editando slot:/)).toBeVisible();

  // Save is not required for preview of persisted create revision
  const frame = page.frameLocator('iframe.preview-frame');
  await expect(frame.locator('body')).toContainText('EdgePHP', {
    timeout: 15000,
  });
});
