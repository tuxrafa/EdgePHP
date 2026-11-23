import { expect, type Locator, type Page, test } from '@playwright/test';

async function pointerDrag(page: Page, source: Locator, target: Locator) {
  const from = await source.boundingBox();
  const to = await target.boundingBox();
  if (!from || !to) throw new Error('Missing drag bounds');
  await page.mouse.move(from.x + from.width / 2, from.y + from.height / 2);
  await page.mouse.down();
  await page.mouse.move(to.x + to.width / 2, to.y + to.height / 2 + 8, {
    steps: 28,
  });
  await page.mouse.up();
}

async function loginAndCreatePage(page: Page, stamp: number) {
  await page.goto('/author/');
  await expect(page.getByTestId('login-form')).toBeVisible();
  await page.getByLabel('Email').fill('author@edgephp.local');
  await page.getByLabel('Senha').fill('author');
  await page.getByTestId('login-submit').click();
  await expect(page.locator('.brand')).toHaveText('EdgePHP');
  await page.getByRole('button', { name: 'Pages' }).click();
  await page.getByLabel('Nova página').fill(`Move ${stamp}`);
  await page.getByLabel('Path').fill(`/move-${stamp}`);
  await page.getByRole('button', { name: 'Criar página' }).click();
}

test('move block to new section via select and remove block/section', async ({
  page,
}) => {
  const stamp = Date.now();
  await loginAndCreatePage(page, stamp);

  await page.getByRole('button', { name: 'Blocks' }).click();
  await page.getByPlaceholder('hero, accordion, carrousel').fill('heading');
  await page.getByRole('button', { name: /^Heading/ }).click();

  const firstSection = page.locator('.section-card').first();
  await expect(firstSection.locator('.block-card')).toHaveCount(1);
  const blockId = await firstSection
    .locator('.block-card')
    .first()
    .getAttribute('data-block-id');
  expect(blockId).toBeTruthy();

  await page.getByTestId('add-section').click();
  const sections = page.locator('.section-card');
  await expect(sections).toHaveCount(2);
  const second = sections.nth(1);
  await expect(second).toHaveClass(/section-active/);
  await expect(second.locator('.block-card')).toHaveCount(0);

  await firstSection
    .locator(`[data-testid="move-${blockId}"]`)
    .selectOption({ label: 'Section 2' });

  await expect(firstSection.locator('.block-card')).toHaveCount(0);
  await expect(second.locator('.block-card')).toHaveCount(1);
  await expect(second.locator('.block-card strong')).toHaveText('heading');

  // Drag back onto empty first section dropzone
  const drag = second.locator(`[data-testid="drag-${blockId}"]`);
  const drop = firstSection.locator(`[data-testid^="drop-"]`);
  await pointerDrag(page, drag, drop);
  await expect(firstSection.locator('.block-card')).toHaveCount(1);
  await expect(second.locator('.block-card')).toHaveCount(0);

  // Remove block
  await firstSection
    .locator(`[data-testid="remove-block-${blockId}"]`)
    .click();
  await expect(firstSection.locator('.block-card')).toHaveCount(0);

  // Remove second section
  const secondId = (await second.getAttribute('data-testid'))?.replace(
    'section-',
    '',
  );
  expect(secondId).toBeTruthy();
  await page.getByTestId(`remove-section-${secondId}`).click();
  await expect(page.locator('.section-card')).toHaveCount(1);
});
