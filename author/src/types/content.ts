export type ContentNode =
  | { id?: string; type: 'paragraph'; text: string }
  | { id?: string; type: 'heading'; level: number; text: string }
  | { id?: string; type: 'image'; src: string; alt?: string; caption?: string; href?: string }
  | { id?: string; type: 'link'; text: string; href: string; variant?: string; newTab?: boolean }
  | { id?: string; type: 'button'; text: string; href: string; variant?: string; newTab?: boolean }
  | { id?: string; type: 'list'; ordered?: boolean; items: Array<string | { text: string }> }
  | { id?: string; type: 'quote'; text: string; author?: string };

export type Cell = {
  id?: string;
  content: ContentNode[];
};

export type Row = {
  id?: string;
  cells: Cell[];
};

export type BlockNode = {
  id?: string;
  type: 'block';
  name: string;
  classes?: string[];
  rows: Row[];
};

export type FragmentReferenceNode = {
  id?: string;
  type: 'fragmentReference';
  fragmentId: string;
  variation?: string;
  mode?: 'linked' | 'detached' | 'pinned';
  pinnedRevisionId?: string | null;
  region?: string;
  sections?: Section[];
};

export type SectionChild = BlockNode | ContentNode | FragmentReferenceNode;

export type Section = {
  id?: string;
  type?: 'section';
  metadata?: Record<string, unknown>;
  children: SectionChild[];
};

export type SlotPayload = {
  sections: Section[];
};

export type PageContent = {
  metadata: Record<string, unknown> & { title?: string };
  sections: Section[];
  templateId?: string | null;
  activeSlotId?: string | null;
  slots?: Record<string, SlotPayload>;
  orphanSlots?: Record<string, SlotPayload>;
};

export type PageRecord = {
  id: string;
  siteId: string;
  locale: string;
  path: string;
  title: string;
  status: string;
  templateId?: string | null;
  draftRevisionId?: string | null;
  publishedRevisionId?: string | null;
  publishedReleaseId?: string | null;
  metadata?: Record<string, unknown>;
  revision?: {
    id: string;
    number: number;
    content: PageContent;
  } | null;
};

export type TemplateLayoutNode =
  | {
      type: 'slot';
      id: string;
      region?: string;
      label?: string;
      editable?: boolean;
      required?: boolean;
      allowedBlocks?: string[];
      allowSections?: boolean;
      allowFragments?: boolean;
    }
  | {
      type: 'fragmentReference';
      id: string;
      region?: string;
      fragmentId: string;
      variation?: string;
      mode?: 'linked' | 'detached' | 'pinned';
      pinnedRevisionId?: string | null;
    }
  | {
      type: 'section';
      id?: string;
      region?: string;
      metadata?: Record<string, unknown>;
      children: SectionChild[];
    };

export type TemplateRecord = {
  id: string;
  siteId: string;
  name: string;
  slug: string;
  description?: string;
  status: string;
  draftRevisionId?: string | null;
  publishedRevisionId?: string | null;
  revision?: {
    id: string;
    number: number;
    layout: TemplateLayoutNode[];
    initialContent?: Record<string, SlotPayload>;
    policies?: Record<string, unknown>;
  } | null;
};

export type FragmentRecord = {
  id: string;
  siteId: string;
  locale?: string;
  name: string;
  slug: string;
  type?: string;
  status: string;
  category?: string;
  tags?: string[];
  defaultVariation?: string;
  draftRevisionId?: string | null;
  publishedRevisionId?: string | null;
  metadata?: Record<string, unknown>;
  revision?: {
    id: string;
    number: number;
    variation?: string;
    content: PageContent;
  } | null;
};

export type BlockDefinition = {
  name: string;
  label: string;
  js: string | null;
  css: string | null;
  category: string;
  description: string;
  icon: string;
  variants: string[];
  fields: Array<Record<string, unknown>>;
  initialStructure: BlockNode | null;
  aliases?: string[];
  compatibility: string;
  warnings: string[];
  rawModel: Record<string, unknown> | null;
  normalizedModel: Record<string, unknown> | null;
  authorManifest?: Record<string, unknown> | null;
};
