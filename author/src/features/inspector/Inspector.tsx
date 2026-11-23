import { useQuery } from '@tanstack/react-query';
import { api } from '../../services/api';
import { useEditorStore } from '../../stores/editorStore';
import type { BlockNode } from '../../types/content';

export default function Inspector() {
  const workspace = useEditorStore((s) => s.workspace);
  const page = useEditorStore((s) => s.page);
  const template = useEditorStore((s) => s.template);
  const fragment = useEditorStore((s) => s.fragment);
  const templates = useEditorStore((s) => s.templates);
  const content = useEditorStore((s) => s.content);
  const selected = useEditorStore((s) => s.selected);
  const dirty = useEditorStore((s) => s.dirty);
  const setBlockVariant = useEditorStore((s) => s.setBlockVariant);
  const removeBlock = useEditorStore((s) => s.removeBlock);
  const removeSection = useEditorStore((s) => s.removeSection);
  const openTemplate = useEditorStore((s) => s.openTemplate);
  const { data: blocks } = useQuery({
    queryKey: ['blocks'],
    queryFn: async () => (await api.listBlocks()).data,
  });

  if (workspace === 'template' && template) {
    return (
      <div className="stack">
        <h3 style={{ margin: 0, fontFamily: 'var(--serif)' }}>Template</h3>
        <div className="field">
          <label>Name</label>
          <input value={template.name} readOnly />
        </div>
        <div className="field">
          <label>Slug</label>
          <input value={template.slug} readOnly />
        </div>
        <p className="muted">{template.description || 'Sem descrição'}</p>
        <p className="muted">
          Draft rev: {template.draftRevisionId?.slice(0, 8)}…
          {template.publishedRevisionId
            ? ` · Published: ${template.publishedRevisionId.slice(0, 8)}…`
            : ' · Não publicado'}
        </p>
        <p className="muted">{dirty ? 'Documento sujo' : 'Documento sincronizado'}</p>
      </div>
    );
  }

  if (workspace === 'fragment' && fragment) {
    return (
      <div className="stack">
        <h3 style={{ margin: 0, fontFamily: 'var(--serif)' }}>Fragment</h3>
        <div className="field">
          <label>Name</label>
          <input value={fragment.name} readOnly />
        </div>
        <p className="muted">
          Shared Experience Fragment · {fragment.status}
        </p>
        <p className="muted">{dirty ? 'Documento sujo' : 'Documento sincronizado'}</p>
      </div>
    );
  }

  if (!page) {
    return <p className="muted">Nenhum elemento selecionado.</p>;
  }

  const pageTemplate = templates.find((t) => t.id === page.templateId);

  const section = content.sections.find((s) => s.id === selected?.sectionId);
  const block = section?.children.find(
    (c) => c.type === 'block' && c.id === selected?.blockId,
  ) as BlockNode | undefined;
  const definition = blocks?.find((b) => b.name === block?.name);
  const currentVariant =
    block?.classes?.find((c) => c !== block.name) ?? 'default';

  return (
    <div className="stack">
      <h3 style={{ margin: 0, fontFamily: 'var(--serif)' }}>Inspector</h3>
      <div className="field">
        <label htmlFor="page-title">Page title</label>
        <input
          id="page-title"
          value={(content.metadata.title as string) ?? page.title}
          onChange={(e) => {
            const next = structuredClone(content);
            next.metadata = { ...next.metadata, title: e.target.value };
            useEditorStore.setState({
              content: next,
              dirty: true,
              page: { ...page, title: e.target.value },
            });
          }}
        />
      </div>
      <div className="field">
        <label>Path</label>
        <input value={page.path} readOnly />
      </div>
      <div className="field">
        <label>Template</label>
        <input
          value={pageTemplate?.name ?? (page.templateId ? page.templateId : 'Blank')}
          readOnly
        />
      </div>
      {page.templateId ? (
        <button
          type="button"
          className="btn"
          onClick={() => void openTemplate(page.templateId!)}
        >
          Abrir template
        </button>
      ) : null}
      {content.activeSlotId ? (
        <p className="muted">Editando slot: {content.activeSlotId}</p>
      ) : null}
      {block ? (
        <>
          <div className="field">
            <label>Selected block</label>
            <input value={block.name} readOnly />
          </div>
          {definition?.description ? (
            <p className="muted">{definition.description}</p>
          ) : null}
          {(definition?.variants?.length ?? 0) > 0 ? (
            <div className="field">
              <label htmlFor="variant">Variant</label>
              <select
                id="variant"
                value={currentVariant}
                onChange={(e) =>
                  setBlockVariant(
                    section?.id ?? '',
                    block.id ?? '',
                    e.target.value,
                  )
                }
                style={{
                  border: '1px solid rgba(255,255,255,0.12)',
                  background: 'rgba(0,0,0,0.25)',
                  color: '#fff',
                  borderRadius: 8,
                  padding: 10,
                }}
              >
                {(definition?.variants ?? ['default']).map((variant) => (
                  <option key={variant} value={variant}>
                    {variant}
                  </option>
                ))}
              </select>
            </div>
          ) : null}
          <div className="field">
            <label>Rows × cells</label>
            <input
              value={`${block.rows.length} rows`}
              readOnly
            />
          </div>
          <button
            type="button"
            className="btn danger-btn"
            data-testid="inspector-remove-block"
            onClick={() =>
              section?.id &&
              block.id &&
              removeBlock(section.id, block.id)
            }
          >
            Remover bloco
          </button>
        </>
      ) : selected?.sectionId ? (
        <>
          <p className="muted">Seção selecionada — sem bloco.</p>
          <button
            type="button"
            className="btn danger-btn"
            data-testid="inspector-remove-section"
            onClick={() => removeSection(selected.sectionId!)}
          >
            Remover seção
          </button>
        </>
      ) : (
        <p className="muted">Selecione um bloco no canvas.</p>
      )}
      <p className="muted">{dirty ? 'Documento sujo' : 'Documento sincronizado'}</p>
    </div>
  );
}
