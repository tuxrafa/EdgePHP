import type { BlockNode, PageContent, Section } from '../types/content';

export function uid(prefix: string): string {
  return `${prefix}-${crypto.randomUUID().slice(0, 8)}`;
}

export function ensureBlockIds(
  block: BlockNode,
  opts: { freshId?: boolean } = {},
): BlockNode {
  const next = structuredClone(block);
  // Always mint a unique sortable id on insert; keep stable ids when hydrating.
  next.id =
    !opts.freshId && next.id && String(next.id).startsWith('block-')
      ? next.id
      : uid('block');
  next.type = 'block';
  next.rows = (next.rows ?? []).map((row) => ({
    ...row,
    id: row.id ?? uid('row'),
    cells: (row.cells ?? []).map((cell) => ({
      ...cell,
      id: cell.id ?? uid('cell'),
      content: (cell.content ?? []).map((node) => ({
        ...node,
        id: 'id' in node && node.id ? node.id : uid('node'),
      })),
    })),
  }));
  return next;
}

export function ensureContentIds(content: PageContent): PageContent {
  const next = structuredClone(content);
  next.sections = (next.sections ?? []).map((section: Section) => ({
    ...section,
    id: section.id ?? uid('section'),
    type: section.type ?? 'section',
    children: (section.children ?? []).map((child) => {
      if (child.type === 'block') {
        return ensureBlockIds(child as BlockNode);
      }
      return {
        ...child,
        id: 'id' in child && child.id ? child.id : uid('node'),
      };
    }),
  }));
  return next;
}
