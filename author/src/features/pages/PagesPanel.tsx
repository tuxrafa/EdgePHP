import { useEffect, useState } from 'react';
import { useEditorStore } from '../../stores/editorStore';

export default function PagesPanel() {
  const pages = useEditorStore((s) => s.pages);
  const page = useEditorStore((s) => s.page);
  const templates = useEditorStore((s) => s.templates);
  const openPage = useEditorStore((s) => s.openPage);
  const createPage = useEditorStore((s) => s.createPage);
  const loadTemplates = useEditorStore((s) => s.loadTemplates);
  const publishPage = useEditorStore((s) => s.publishPage);
  const [title, setTitle] = useState('Home');
  const [path, setPath] = useState('/home');
  const [templateId, setTemplateId] = useState<string>('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    void loadTemplates();
  }, [loadTemplates]);

  async function onCreate() {
    setBusy(true);
    try {
      await createPage(
        title.trim() || 'Home',
        path.trim() || '/home',
        templateId || null,
      );
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="stack">
      <div className="field">
        <label htmlFor="new-title">Nova página</label>
        <input
          id="new-title"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
        />
      </div>
      <div className="field">
        <label htmlFor="new-path">Path</label>
        <input
          id="new-path"
          value={path}
          onChange={(e) => setPath(e.target.value)}
        />
      </div>
      <div className="field">
        <label htmlFor="new-template">Template</label>
        <select
          id="new-template"
          value={templateId}
          onChange={(e) => setTemplateId(e.target.value)}
        >
          <option value="">Blank (sem template)</option>
          {templates.map((tpl) => (
            <option key={tpl.id} value={tpl.id}>
              {tpl.name}
              {tpl.publishedRevisionId ? '' : ' (draft)'}
            </option>
          ))}
        </select>
      </div>
      <button
        className="btn primary"
        type="button"
        disabled={busy}
        onClick={() => void onCreate()}
      >
        Criar página
      </button>
      {page ? (
        <button
          className="btn"
          type="button"
          onClick={() => void publishPage()}
        >
          Publicar página aberta
        </button>
      ) : null}
      <hr style={{ borderColor: 'rgba(255,255,255,0.08)', width: '100%' }} />
      {pages.map((item) => (
        <button
          key={item.id}
          type="button"
          className={`list-item ${page?.id === item.id ? 'active' : ''}`}
          onClick={() => void openPage(item.id)}
        >
          {item.title}
          <small>
            {item.path} · {item.status}
            {item.templateId ? ' · templated' : ''}
          </small>
        </button>
      ))}
      {!pages.length ? <p className="muted">Nenhuma página ainda.</p> : null}
    </div>
  );
}
