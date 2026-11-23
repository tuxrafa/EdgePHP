import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { api } from '../../services/api';
import { useEditorStore } from '../../stores/editorStore';
import type { BlockDefinition, BlockNode } from '../../types/content';
import { ensureBlockIds } from '../../lib/ids';

const CATEGORY_LABELS: Record<string, string> = {
  content: 'Conteúdo básico',
  layout: 'Layout',
  presentation: 'Hero e apresentação',
  collections: 'Cards e coleções',
  interactive: 'Interativos',
  media: 'Mídia',
  uncategorized: 'Outros',
};

export default function BlocksPanel() {
  const insertBlockNode = useEditorStore((s) => s.insertBlockNode);
  const selected = useEditorStore((s) => s.selected);
  const page = useEditorStore((s) => s.page);
  const fragment = useEditorStore((s) => s.fragment);
  const workspace = useEditorStore((s) => s.workspace);
  const canEditBlocks = workspace === 'page' ? !!page : workspace === 'fragment' ? !!fragment : false;
  const [query, setQuery] = useState('');
  const { data, isLoading, error } = useQuery({
    queryKey: ['blocks'],
    queryFn: async () => (await api.listBlocks()).data,
  });

  const grouped = useMemo(() => {
    const q = query.trim().toLowerCase();
    const list = (data ?? []).filter((block) => {
      if (!q) return true;
      const hay = [
        block.name,
        block.label,
        block.description,
        ...(block.aliases ?? []),
        block.category,
      ]
        .join(' ')
        .toLowerCase();
      return hay.includes(q) || (q === 'carrousel' && block.name === 'carousel');
    });
    const map = new Map<string, BlockDefinition[]>();
    for (const block of list) {
      const key = block.category || 'uncategorized';
      if (!map.has(key)) map.set(key, []);
      map.get(key)!.push(block);
    }
    return [...map.entries()];
  }, [data, query]);

  if (isLoading) return <p className="muted">Escaneando blocks/…</p>;
  if (error) return <p className="error">{(error as Error).message}</p>;

  return (
    <div className="stack">
      <p className="muted">
        Biblioteca EDS-shaped. JS/CSS dos blocos não são reescritos como React.
        {selected?.sectionId
          ? ' Inserindo na seção selecionada.'
          : ' Selecione uma seção no canvas/structure para direcionar a inserção.'}
      </p>
      <div className="field">
        <label htmlFor="block-search">Buscar</label>
        <input
          id="block-search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="hero, accordion, carrousel…"
        />
      </div>
      {grouped.map(([category, blocks]) => (
        <div key={category} className="stack">
          <strong style={{ fontSize: 12, letterSpacing: '0.06em', textTransform: 'uppercase', color: 'rgba(232,226,216,0.55)' }}>
            {CATEGORY_LABELS[category] ?? category}
          </strong>
          {blocks.map((block) => (
            <button
              key={block.name}
              type="button"
              className="list-item"
              disabled={!canEditBlocks}
              onClick={() => {
                const structure = (block.initialStructure as BlockNode | null) ?? {
                  type: 'block' as const,
                  name: block.name,
                  rows: [
                    {
                      cells: [
                        {
                          content: [{ type: 'paragraph' as const, text: block.label }],
                        },
                      ],
                    },
                  ],
                };
                insertBlockNode(
                  ensureBlockIds({ ...structure, name: block.name }),
                  selected?.sectionId,
                );
              }}
            >
              {block.label}
              <small>
                {block.compatibility}
                {block.description ? ` · ${block.description}` : ''}
              </small>
            </button>
          ))}
        </div>
      ))}
      {!canEditBlocks ? (
        <p className="muted">Abra uma página ou fragmento para inserir blocos.</p>
      ) : null}
    </div>
  );
}
