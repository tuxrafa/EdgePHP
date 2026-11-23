import { create } from 'zustand';
import type {
  BlockNode,
  FragmentRecord,
  PageContent,
  PageRecord,
  Section,
  TemplateLayoutNode,
  TemplateRecord,
} from '../types/content';
import { api } from '../services/api';
import { ensureBlockIds, ensureContentIds, uid } from '../lib/ids';

export type WorkspaceMode = 'page' | 'fragment' | 'template';

function pickPrimarySlotId(
  slots: NonNullable<PageContent['slots']>,
): string {
  if (slots.main) return 'main';
  if (slots['article-content']) return 'article-content';
  return Object.keys(slots)[0] ?? 'main';
}

function authorContentFromRaw(
  raw: PageContent | undefined,
  title: string,
  templateId?: string | null,
): PageContent {
  const base = raw ?? emptyContent(title);
  const resolvedTemplateId = templateId ?? base.templateId ?? null;
  if (base.slots && Object.keys(base.slots).length > 0) {
    const activeSlotId = base.activeSlotId ?? pickPrimarySlotId(base.slots);
    const slotSections = base.slots[activeSlotId]?.sections ?? [];
    return ensureContentIds({
      ...base,
      templateId: resolvedTemplateId,
      activeSlotId,
      sections: slotSections.length ? slotSections : emptyContent(title).sections,
    });
  }
  const withSections =
    base.sections?.length ? base : emptyContent(title);
  return ensureContentIds({
    ...withSections,
    templateId: resolvedTemplateId,
  });
}

function packPageContentForSave(content: PageContent): PageContent {
  const title = (content.metadata?.title as string | undefined) ?? 'Untitled';
  if (content.templateId && content.activeSlotId) {
    const slots = { ...(content.slots ?? {}) };
    slots[content.activeSlotId] = { sections: content.sections };
    return {
      metadata: { ...content.metadata, title },
      templateId: content.templateId,
      slots,
      orphanSlots: content.orphanSlots ?? {},
      sections: content.sections,
      activeSlotId: content.activeSlotId,
    };
  }
  return {
    metadata: { ...content.metadata, title },
    sections: content.sections,
  };
}

function emptyContent(title = 'Untitled'): PageContent {
  return {
    metadata: { title },
    sections: [
      {
        id: uid('section'),
        type: 'section',
        metadata: {},
        children: [],
      },
    ],
  };
}

function defaultBlock(name: string): BlockNode {
  if (name === 'columns') {
    return ensureBlockIds({
      type: 'block',
      name: 'columns',
      rows: [
        {
          cells: [
            { content: [{ type: 'paragraph', text: 'Left' }] },
            { content: [{ type: 'paragraph', text: 'Right' }] },
          ],
        },
      ],
    });
  }

  if (name === 'hero') {
    return ensureBlockIds({
      type: 'block',
      name: 'hero',
      classes: ['split'],
      rows: [
        {
          cells: [
            {
              content: [
                { type: 'paragraph', text: 'NOVO' },
                {
                  type: 'heading',
                  level: 1,
                  text: 'Construa sites com blocos EDS',
                },
                { type: 'paragraph', text: 'Author visual + runtime PHP.' },
                {
                  type: 'link',
                  text: 'Começar',
                  href: '#',
                  variant: 'primary',
                },
              ],
            },
            {
              content: [
                {
                  type: 'image',
                  src: 'https://placehold.co/900x700/png',
                  alt: 'Hero',
                },
              ],
            },
          ],
        },
      ],
    });
  }

  return ensureBlockIds({
    type: 'block',
    name,
    rows: [
      {
        cells: [
          { content: [{ type: 'paragraph', text: name }] },
        ],
      },
    ],
  });
}

type EditorState = {
  user: { id: number; name: string; email: string } | null;
  workspace: WorkspaceMode;
  pages: PageRecord[];
  page: PageRecord | null;
  templates: TemplateRecord[];
  template: TemplateRecord | null;
  templateLayout: TemplateLayoutNode[];
  fragments: FragmentRecord[];
  fragment: FragmentRecord | null;
  content: PageContent;
  dirty: boolean;
  saving: boolean;
  selected: { sectionId?: string; blockId?: string } | null;
  previewKey: number;
  error: string | null;
  leftTab: 'pages' | 'templates' | 'fragments' | 'blocks' | 'structure';
  setLeftTab: (tab: EditorState['leftTab']) => void;
  setUser: (user: EditorState['user']) => void;
  loadPages: () => Promise<void>;
  loadTemplates: () => Promise<void>;
  loadFragments: () => Promise<void>;
  openPage: (id: string) => Promise<void>;
  createPage: (
    title: string,
    path: string,
    templateId?: string | null,
  ) => Promise<void>;
  publishPage: () => Promise<void>;
  openTemplate: (id: string) => Promise<void>;
  createTemplate: (name: string, description?: string) => Promise<void>;
  publishTemplate: () => Promise<void>;
  openFragment: (id: string) => Promise<void>;
  createFragment: (name: string, category?: string) => Promise<void>;
  publishFragment: () => Promise<void>;
  setTemplateLayout: (layout: TemplateLayoutNode[]) => void;
  insertFragmentReference: (
    fragmentId: string,
    mode?: 'linked' | 'detached' | 'pinned',
  ) => void;
  insertBlock: (name: string, sectionId?: string) => void;
  insertBlockNode: (block: BlockNode, sectionId?: string) => void;
  moveBlock: (activeBlockId: string, overId: string) => void;
  removeBlock: (sectionId: string, blockId: string) => void;
  removeSection: (sectionId: string) => void;
  updateParagraph: (
    sectionId: string,
    blockId: string,
    cellIndex: number,
    text: string,
  ) => void;
  updateNodeText: (
    sectionId: string,
    blockId: string,
    rowIndex: number,
    cellIndex: number,
    nodeIndex: number,
    text: string,
  ) => void;
  setBlockVariant: (sectionId: string, blockId: string, variant: string) => void;
  select: (selection: EditorState['selected']) => void;
  addSection: () => void;
  save: () => Promise<void>;
  bumpPreview: () => void;
};

function findBlockRef(
  content: PageContent,
  blockId: string,
): { sectionIndex: number; childIndex: number } | null {
  for (let sectionIndex = 0; sectionIndex < content.sections.length; sectionIndex++) {
    const children = content.sections[sectionIndex]?.children ?? [];
    const childIndex = children.findIndex(
      (child) => child.type === 'block' && child.id === blockId,
    );
    if (childIndex >= 0) {
      return { sectionIndex, childIndex };
    }
  }
  return null;
}

export function sectionDropId(sectionId: string): string {
  return `section:${sectionId}`;
}

export function parseSectionDropId(overId: string): string | null {
  return overId.startsWith('section:') ? overId.slice('section:'.length) : null;
}

export const useEditorStore = create<EditorState>((set, get) => ({
  user: null,
  workspace: 'page',
  pages: [],
  page: null,
  templates: [],
  template: null,
  templateLayout: [],
  fragments: [],
  fragment: null,
  content: emptyContent(),
  dirty: false,
  saving: false,
  selected: null,
  previewKey: 0,
  error: null,
  leftTab: 'pages',
  setLeftTab: (leftTab) => set({ leftTab }),
  setUser: (user) => set({ user }),
  loadPages: async () => {
    const { data } = await api.listPages();
    set({ pages: data });
  },
  loadTemplates: async () => {
    const { data } = await api.listTemplates();
    set({ templates: data });
  },
  loadFragments: async () => {
    const { data } = await api.listFragments();
    set({ fragments: data });
  },
  openPage: async (id) => {
    const { data } = await api.getPage(id);
    const content = authorContentFromRaw(
      data.revision?.content,
      data.title,
      data.templateId,
    );
    set({
      workspace: 'page',
      page: data,
      fragment: null,
      template: null,
      content,
      dirty: false,
      selected: {
        sectionId: content.sections[0]?.id,
      },
      previewKey: Date.now(),
      error: null,
      leftTab: 'structure',
    });
  },
  createPage: async (title, path, templateId = null) => {
    const payload = templateId
      ? { title, path, templateId }
      : {
          title,
          path,
          metadata: { title },
          sections: emptyContent(title).sections,
        };
    const { data } = await api.createPage(payload);
    await get().loadPages();
    await get().openPage(data.id);
  },
  publishPage: async () => {
    const { page } = get();
    if (!page) return;
    set({ saving: true, error: null });
    try {
      if (get().dirty) {
        await get().save();
      }
      const { data } = await api.publishPage(page.id);
      set({
        page: data,
        saving: false,
        previewKey: Date.now(),
      });
    } catch (error) {
      set({
        saving: false,
        error: error instanceof Error ? error.message : 'Publish failed',
      });
      throw error;
    }
  },
  openTemplate: async (id) => {
    const { data } = await api.getTemplate(id);
    set({
      workspace: 'template',
      template: data,
      templateLayout: data.revision?.layout ?? [],
      page: null,
      fragment: null,
      content: emptyContent(data.name),
      dirty: false,
      selected: null,
      previewKey: Date.now(),
      error: null,
      leftTab: 'templates',
    });
  },
  createTemplate: async (name, description = '') => {
    const { data } = await api.createTemplate({ name, description });
    await get().loadTemplates();
    await get().openTemplate(data.id);
  },
  publishTemplate: async () => {
    const { template } = get();
    if (!template) return;
    if (get().dirty) await get().save();
    const { data } = await api.publishTemplate(template.id);
    set({ template: data, previewKey: Date.now() });
    await get().loadTemplates();
  },
  openFragment: async (id) => {
    const { data } = await api.getFragment(id);
    const content = ensureContentIds(
      data.revision?.content ?? emptyContent(data.name),
    );
    set({
      workspace: 'fragment',
      fragment: data,
      page: null,
      template: null,
      content,
      dirty: false,
      selected: { sectionId: content.sections[0]?.id },
      previewKey: Date.now(),
      error: null,
      leftTab: 'fragments',
    });
  },
  createFragment: async (name, category = 'general') => {
    const { data } = await api.createFragment({ name, category });
    await get().loadFragments();
    await get().openFragment(data.id);
  },
  publishFragment: async () => {
    const { fragment } = get();
    if (!fragment) return;
    if (get().dirty) await get().save();
    const { data } = await api.publishFragment(fragment.id);
    set({ fragment: data, previewKey: Date.now() });
    await get().loadFragments();
  },
  setTemplateLayout: (layout) =>
    set({ templateLayout: layout, dirty: true }),
  insertFragmentReference: (fragmentId, mode = 'linked') => {
    const content = structuredClone(get().content);
    const sectionId =
      get().selected?.sectionId ?? content.sections.at(-1)?.id;
    let section = content.sections.find((s) => s.id === sectionId);
    if (!section) {
      section = {
        id: uid('section'),
        type: 'section',
        children: [],
      };
      content.sections.push(section);
    }
    section.children.push({
      id: uid('fragref'),
      type: 'fragmentReference',
      fragmentId,
      variation: 'default',
      mode,
    });
    set({ content, dirty: true, selected: { sectionId: section.id } });
  },
  insertBlock: (name, sectionId) => {
    get().insertBlockNode(defaultBlock(name), sectionId);
  },
  insertBlockNode: (block, sectionId) => {
    const content = structuredClone(get().content);
    const selectedSectionId = get().selected?.sectionId;
    const targetId =
      sectionId ?? selectedSectionId ?? content.sections.at(-1)?.id;

    let section = content.sections.find((s) => s.id === targetId);
    if (!section) {
      section = content.sections.at(-1);
    }
    if (!section) {
      const fresh: Section = {
        id: uid('section'),
        type: 'section',
        children: [],
      };
      content.sections.push(fresh);
      section = fresh;
    }

    if (!Array.isArray(section.children)) {
      section.children = [];
    }

    const normalized = ensureBlockIds(block, { freshId: true });
    section.children.push(normalized);
    set({
      content,
      dirty: true,
      selected: { sectionId: section.id, blockId: normalized.id },
    });
  },
  moveBlock: (activeBlockId, overId) => {
    if (!activeBlockId || !overId || activeBlockId === overId) return;
    const content = structuredClone(get().content);
    const from = findBlockRef(content, activeBlockId);
    if (!from) return;

    const sourceSection = content.sections[from.sectionIndex];
    if (!sourceSection) return;

    const dropSectionId = parseSectionDropId(overId);
    const overBlock = dropSectionId ? null : findBlockRef(content, overId);

    const [moved] = sourceSection.children.splice(from.childIndex, 1);
    if (!moved) return;

    if (dropSectionId) {
      const target = content.sections.find((s) => s.id === dropSectionId);
      if (!target) {
        sourceSection.children.splice(from.childIndex, 0, moved);
        return;
      }
      if (!Array.isArray(target.children)) target.children = [];
      target.children.push(moved);
      set({
        content,
        dirty: true,
        selected: { sectionId: target.id, blockId: activeBlockId },
      });
      return;
    }

    if (!overBlock) {
      sourceSection.children.splice(from.childIndex, 0, moved);
      return;
    }

    const targetSection = content.sections[overBlock.sectionIndex];
    if (!targetSection) {
      sourceSection.children.splice(from.childIndex, 0, moved);
      return;
    }

    // arrayMove semantics: use the pre-removal over index on the post-splice list.
    const insertAt = overBlock.childIndex;
    targetSection.children.splice(insertAt, 0, moved);
    set({
      content,
      dirty: true,
      selected: { sectionId: targetSection.id, blockId: activeBlockId },
    });
  },
  removeBlock: (sectionId, blockId) => {
    if (!blockId) return;
    const content = structuredClone(get().content);
    let section = sectionId
      ? content.sections.find((s) => s.id === sectionId)
      : undefined;
    if (
      !section?.children.some(
        (child) => child.type === 'block' && child.id === blockId,
      )
    ) {
      const ref = findBlockRef(content, blockId);
      if (!ref) return;
      section = content.sections[ref.sectionIndex];
    }
    if (!section) return;
    section.children = section.children.filter(
      (child) => !(child.type === 'block' && child.id === blockId),
    );
    const selected = get().selected;
    set({
      content,
      dirty: true,
      selected:
        selected?.blockId === blockId
          ? { sectionId: section.id }
          : selected,
    });
  },
  removeSection: (sectionId) => {
    if (!sectionId) return;
    const content = structuredClone(get().content);
    if (content.sections.length <= 1) {
      // Keep a single empty section instead of wiping the page.
      const only = content.sections[0];
      if (only) {
        only.children = [];
        set({
          content,
          dirty: true,
          selected: { sectionId: only.id },
        });
      }
      return;
    }
    content.sections = content.sections.filter((s) => s.id !== sectionId);
    const fallback = content.sections[0];
    set({
      content,
      dirty: true,
      selected: { sectionId: fallback?.id },
    });
  },
  updateParagraph: (sectionId, blockId, cellIndex, text) => {
    get().updateNodeText(sectionId, blockId, 0, cellIndex, 0, text);
  },
  updateNodeText: (sectionId, blockId, rowIndex, cellIndex, nodeIndex, text) => {
    const content = structuredClone(get().content);
    const section = content.sections.find((s) => s.id === sectionId);
    if (!section) return;
    const block = section.children.find(
      (c) => c.type === 'block' && c.id === blockId,
    ) as BlockNode | undefined;
    if (!block) return;
    const node = block.rows[rowIndex]?.cells[cellIndex]?.content[nodeIndex];
    if (!node) return;
    if (
      node.type === 'paragraph' ||
      node.type === 'heading' ||
      node.type === 'link' ||
      node.type === 'button' ||
      node.type === 'quote'
    ) {
      node.text = text;
    } else if (node.type === 'image') {
      node.alt = text;
    }
    set({ content, dirty: true });
  },
  setBlockVariant: (sectionId, blockId, variant) => {
    const content = structuredClone(get().content);
    const section = content.sections.find((s) => s.id === sectionId);
    if (!section) return;
    const block = section.children.find(
      (c) => c.type === 'block' && c.id === blockId,
    ) as BlockNode | undefined;
    if (!block) return;
    const reserved = new Set([block.name, 'block']);
    block.classes = (block.classes ?? []).filter((c) => !reserved.has(c));
    if (variant && variant !== 'default') {
      block.classes.push(variant);
    }
    set({ content, dirty: true });
  },
  select: (selected) => set({ selected }),
  addSection: () => {
    const content = structuredClone(get().content);
    const section: Section = {
      id: uid('section'),
      type: 'section',
      children: [],
    };
    content.sections.push(section);
    set({ content, dirty: true, selected: { sectionId: section.id } });
  },
  save: async () => {
    const state = get();
    set({ saving: true, error: null });
    try {
      if (state.workspace === 'fragment' && state.fragment) {
        const title =
          (state.content.metadata?.title as string | undefined) ??
          state.fragment.name;
        const { data } = await api.updateFragment(state.fragment.id, {
          name: state.fragment.name,
          content: {
            metadata: { ...state.content.metadata, title },
            sections: state.content.sections,
          },
          expectedRevisionId: state.fragment.draftRevisionId,
          message: 'Fragment save',
        });
        const next = ensureContentIds(
          data.revision?.content ?? state.content,
        );
        set({
          fragment: data,
          content: next,
          dirty: false,
          saving: false,
          previewKey: Date.now(),
        });
        return;
      }

      if (state.workspace === 'template' && state.template) {
        const { data } = await api.updateTemplate(state.template.id, {
          layout: state.templateLayout,
          expectedRevisionId: state.template.draftRevisionId,
          message: 'Template save',
        });
        set({
          template: data,
          templateLayout: data.revision?.layout ?? state.templateLayout,
          dirty: false,
          saving: false,
          previewKey: Date.now(),
        });
        return;
      }

      const { page, content } = state;
      if (!page) {
        set({ saving: false });
        return;
      }
      const title =
        (content.metadata?.title as string | undefined) ?? page.title;
      const packed = packPageContentForSave({
        ...content,
        metadata: { ...content.metadata, title },
      });
      const { data } = await api.updatePage(page.id, {
        title,
        content: packed,
        expectedRevisionId: page.draftRevisionId,
        message: 'Author save',
      });
      const nextContent = authorContentFromRaw(
        data.revision?.content,
        data.title,
        data.templateId,
      );
      set({
        page: data,
        content: nextContent,
        dirty: false,
        saving: false,
        previewKey: Date.now(),
        selected: {
          sectionId:
            get().selected?.sectionId ?? nextContent.sections[0]?.id,
          blockId: get().selected?.blockId,
        },
      });
    } catch (error) {
      set({
        saving: false,
        error: error instanceof Error ? error.message : 'Save failed',
      });
      throw error;
    }
  },
  bumpPreview: () => set({ previewKey: Date.now() }),
}));
