import { expect, type Locator, type Page, test } from '@playwright/test';

async function pointerDrag(page: Page, source: Locator, target: Locator) {
  const from = await source.boundingBox();
  const to = await target.boundingBox();
  if (!from || !to) {
    throw new Error('Missing drag bounds');
  }
  await page.mouse.move(from.x + from.width / 2, from.y + from.height / 2);
  await page.mouse.down();
  await page.mouse.move(to.x + to.width / 2, to.y + to.height / 2 + 12, {
    steps: 28,
  });
  await page.mouse.up();
}

test('insert into new section and reorder blocks', async ({ page }) => {
  await page.goto('/author/');
  await expect(page.getByTestId('login-form')).toBeVisible();
  await page.getByLabel('Email').fill('author@edgephp.local');
  await page.getByLabel('Senha').fill('author');
  await page.getByTestId('login-submit').click();
  await expect(page.locator('.brand')).toHaveText('EdgePHP');

  const stamp = Date.now();
  await page.getByRole('button', { name: 'Pages' }).click();
  await page.getByLabel('Nova página').fill(`DnD ${stamp}`);
  await page.getByLabel('Path').fill(`/dnd-${stamp}`);
  await page.getByRole('button', { name: 'Criar página' }).click();

  await page.getByRole('button', { name: 'Blocks' }).click();
  await page.getByPlaceholder('hero, accordion, carrousel').fill('heading');
  await page.getByRole('button', { name: /^Heading/ }).click();

  await page.getByTestId('add-section').click();
  const sectionCards = page.locator('.section-card');
  await expect(sectionCards).toHaveCount(2);
  const second = sectionCards.nth(1);
  await expect(second).toHaveClass(/section-active/);
  await second.getByRole('button', { name: '+ Columns aqui' }).click();
  await expect(second.locator('.block-card')).toHaveCount(1);
  await expect(second.locator('.block-card strong')).toHaveText('columns');

  const firstSection = sectionCards.nth(0);
  await firstSection.getByRole('heading', { name: 'Section 1' }).click();
  await expect(firstSection).toHaveClass(/section-active/);
  await page.getByRole('button', { name: 'Blocks' }).click();
  await page.getByPlaceholder('hero, accordion, carrousel').fill('text');
  await page.getByRole('button', { name: /^Text/ }).click();
  await expect(firstSection.locator('.block-card')).toHaveCount(2);

  const firstIds = await firstSection.locator('.block-card').evaluateAll((els) =>
    els.map((el) => el.getAttribute('data-block-id')),
  );
  expect(firstIds[0]).toBeTruthy();
  expect(firstIds[1]).toBeTruthy();
  expect(firstIds[0]).not.toEqual(firstIds[1]);

  const drag = firstSection.locator(`[data-testid="drag-${firstIds[0]}"]`);
  const target = firstSection.locator(`[data-testid="drag-${firstIds[1]}"]`);
  await pointerDrag(page, drag, target);

  await expect
    .poll(async () =>
      firstSection.locator('.block-card').evaluateAll((els) =>
        els.map((el) => el.getAttribute('data-block-id')),
      ),
    )
    .toEqual([firstIds[1], firstIds[0]]);
});
