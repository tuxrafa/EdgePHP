import { expect, test } from '@playwright/test';

test('pages console drills into a virtual folder and returns to the same trail', async ({
  page,
}) => {
  const stamp = Date.now();
  const folder = `pc${stamp}`;
  const folderTitle = `Pc${stamp}`;

  await page.goto('/author/pages');
  await expect(page.getByTestId('login-form')).toBeVisible();
  await page.getByLabel('Email').fill('author@edgephp.local');
  await page.getByLabel('Senha').fill('author');
  await page.getByTestId('login-submit').click();

  await expect(page.getByTestId('pages-console')).toBeVisible();

  await page.evaluate(async (segment) => {
    for (const article of ['Artigo A', 'Artigo B']) {
      const slug = article === 'Artigo A' ? 'artigo-a' : 'artigo-b';
      const response = await fetch('/api/v1/pages', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
          path: `/${segment}/${slug}`,
          title: article,
          sections: [{ children: [{ type: 'paragraph', text: article }] }],
        }),
      });
      if (!response.ok) {
        throw new Error(await response.text());
      }
    }
  }, folder);

  await page.getByRole('button', { name: 'Refresh' }).click();
  const folderItem = page.getByRole('treeitem', { name: `Pasta ${folderTitle}` });
  await expect(folderItem).toBeVisible();
  await folderItem.click();

  const article = page.getByRole('treeitem', { name: 'Página Artigo A' });
  await expect(article).toBeVisible();
  await article.click();
  await page.keyboard.press('ArrowDown');
  await expect(page.getByRole('treeitem', { name: 'Página Artigo B' })).toHaveAttribute(
    'aria-selected',
    'true',
  );
  await page.keyboard.press('Enter');

  await expect(page.getByTestId('back-to-pages')).toBeVisible();
  await expect(page.locator('.topbar .meta span')).toContainText(`/${folder}/artigo-b`);
  await page.getByTestId('back-to-pages').click();

  await expect(page.getByTestId('pages-console')).toBeVisible();
  await expect(page.getByRole('treeitem', { name: `Pasta ${folderTitle}` })).toHaveAttribute(
    'aria-selected',
    'true',
  );
  await expect(page.getByRole('treeitem', { name: 'Página Artigo B' })).toBeVisible();
});
