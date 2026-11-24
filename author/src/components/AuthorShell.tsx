import { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useEditorStore } from '../stores/editorStore';
import PagesPanel from '../features/pages/PagesPanel';
import BlocksPanel from '../features/blocks/BlocksPanel';
import StructurePanel from '../features/pages/StructurePanel';
import TemplatesPanel from '../features/templates/TemplatesPanel';
import FragmentsPanel from '../features/fragments/FragmentsPanel';
import Canvas from '../features/canvas/Canvas';
import Inspector from '../features/inspector/Inspector';
import PreviewFrame from '../features/preview/PreviewFrame';

export default function AuthorShell() {
  const navigate = useNavigate();
  const { pageId } = useParams();
  const workspace = useEditorStore((s) => s.workspace);
  const page = useEditorStore((s) => s.page);
  const template = useEditorStore((s) => s.template);
  const fragment = useEditorStore((s) => s.fragment);
  const dirty = useEditorStore((s) => s.dirty);
  const saving = useEditorStore((s) => s.saving);
  const error = useEditorStore((s) => s.error);
  const leftTab = useEditorStore((s) => s.leftTab);
  const setLeftTab = useEditorStore((s) => s.setLeftTab);
  const save = useEditorStore((s) => s.save);
  const loadPages = useEditorStore((s) => s.loadPages);
  const loadTemplates = useEditorStore((s) => s.loadTemplates);
  const loadFragments = useEditorStore((s) => s.loadFragments);
  const user = useEditorStore((s) => s.user);
  const openPage = useEditorStore((s) => s.openPage);

  useEffect(() => {
    void loadPages();
    void loadTemplates();
    void loadFragments();
  }, [loadPages, loadTemplates, loadFragments]);

  useEffect(() => {
    if (!pageId) return;
    if (useEditorStore.getState().page?.id === pageId) return;
    void openPage(pageId);
  }, [pageId, openPage]);

  function backToPages() {
    if (dirty && !window.confirm('Há alterações não salvas. Voltar ao gerenciador de páginas?')) {
      return;
    }
    navigate('/pages');
  }

  const title =
    workspace === 'template'
      ? template?.name ?? 'Template'
      : workspace === 'fragment'
        ? fragment?.name ?? 'Fragment'
        : (page?.title ?? 'Nenhuma página');

  const subtitle =
    workspace === 'template'
      ? `template · ${template?.slug ?? ''}`
      : workspace === 'fragment'
        ? `fragment · ${fragment?.slug ?? ''}`
        : (page?.path ?? 'Selecione ou crie uma página');

  const canSave =
    dirty &&
    !saving &&
    ((workspace === 'page' && !!page) ||
      (workspace === 'fragment' && !!fragment) ||
      (workspace === 'template' && !!template));

  return (
    <div className="shell">
      <header className="topbar">
        <div className="brand">EdgePHP</div>
        <button
          type="button"
          className="btn"
          data-testid="back-to-pages"
          onClick={backToPages}
        >
          Back to Pages
        </button>
        <div className="meta">
          <strong>{title}</strong>
          <span>{subtitle}</span>
        </div>
        <div className="spacer" />
        <span className={`badge ${dirty ? 'dirty' : ''}`}>
          {dirty ? 'Alterações não salvas' : 'Salvo'}
        </span>
        <span className="badge draft">
          {workspace === 'page'
            ? (page?.status ?? 'draft')
            : workspace === 'fragment'
              ? (fragment?.status ?? 'draft')
              : (template?.status ?? 'draft')}
        </span>
        <span className="badge">{user?.email}</span>
        <button
          className="btn primary"
          type="button"
          disabled={!canSave}
          onClick={() => void save()}
        >
          {saving ? 'Salvando…' : 'Save'}
        </button>
      </header>

      <div className="workspace">
        <aside className="panel">
          <div className="tabs">
            {(
              [
                ['pages', 'Pages'],
                ['templates', 'Templates'],
                ['fragments', 'Fragments'],
                ['blocks', 'Blocks'],
                ['structure', 'Structure'],
              ] as const
            ).map(([id, label]) => (
              <button
                key={id}
                type="button"
                className={leftTab === id ? 'active' : ''}
                onClick={() => setLeftTab(id)}
              >
                {label}
              </button>
            ))}
          </div>
          <div className="panel-body">
            {leftTab === 'pages' ? <PagesPanel /> : null}
            {leftTab === 'templates' ? <TemplatesPanel /> : null}
            {leftTab === 'fragments' ? <FragmentsPanel /> : null}
            {leftTab === 'blocks' ? <BlocksPanel /> : null}
            {leftTab === 'structure' ? <StructurePanel /> : null}
          </div>
        </aside>

        <main className="center">
          <section className="canvas">
            {error ? <div className="error">{error}</div> : null}
            {workspace === 'template' ? (
              <div className="section-card">
                <h3>Editor de Template</h3>
                <p>
                  Monte o layout no painel Templates (slots + fragmentos). O
                  canvas de blocos edita páginas e fragments; o preview abaixo
                  compõe o template via PHP ContentComposer.
                </p>
              </div>
            ) : (
              <Canvas />
            )}
          </section>
          <section className="preview-pane">
            <div className="row">
              <strong>Preview EDS</strong>
              <span className="muted">
                {workspace === 'page'
                  ? 'página composta'
                  : workspace === 'fragment'
                    ? 'fragmento'
                    : 'template'}{' '}
                → PHP + aem.js
              </span>
            </div>
            <PreviewFrame />
          </section>
        </main>

        <aside className="panel right">
          <div className="panel-body">
            <Inspector />
          </div>
        </aside>
      </div>
    </div>
  );
}
