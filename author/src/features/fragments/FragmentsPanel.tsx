import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { useEditorStore } from '../../stores/editorStore';

export default function FragmentsPanel() {
  const fragments = useEditorStore((s) => s.fragments);
  const fragment = useEditorStore((s) => s.fragment);
  const loadFragments = useEditorStore((s) => s.loadFragments);
  const openFragment = useEditorStore((s) => s.openFragment);
  const createFragment = useEditorStore((s) => s.createFragment);
  const publishFragment = useEditorStore((s) => s.publishFragment);
  const insertFragmentReference = useEditorStore(
    (s) => s.insertFragmentReference,
  );
  const workspace = useEditorStore((s) => s.workspace);
  const [name, setName] = useState('Banner');
  const [busy, setBusy] = useState(false);
  const [usage, setUsage] = useState<string>('');

  useEffect(() => {
    void loadFragments();
  }, [loadFragments]);

  async function onCreate() {
    setBusy(true);
    try {
      await createFragment(name.trim() || 'Fragment');
    } finally {
      setBusy(false);
    }
  }

  async function showUsage(id: string) {
    const { data } = await api.fragmentUsage(id);
    setUsage(
      `Templates: ${data.templates.length} · Pages: ${data.pages.length} · Indiretas: ${data.indirectPageCount}`,
    );
  }

  return (
    <div className="stack">
      <div className="field">
        <label htmlFor="frag-name">Novo fragmento</label>
        <input
          id="frag-name"
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
      </div>
      <button
        className="btn primary"
        type="button"
        disabled={busy}
        onClick={() => void onCreate()}
      >
        Criar fragmento
      </button>
      <p className="muted">
        Experience Fragments reutilizam o Canvas de blocos. Inserção em página:
        linked / detached.
      </p>
      <hr style={{ borderColor: 'rgba(255,255,255,0.08)', width: '100%' }} />
      {fragments.map((item) => (
        <div key={item.id} className="stack">
          <button
            type="button"
            className={`list-item ${fragment?.id === item.id ? 'active' : ''}`}
            onClick={() => void openFragment(item.id)}
          >
            {item.name}
            <small>
              {item.slug} · {item.status} · {item.category}
            </small>
          </button>
          {workspace === 'page' ? (
            <div className="row" style={{ gap: 4, marginLeft: 8 }}>
              <button
                type="button"
                className="btn"
                onClick={() => insertFragmentReference(item.id, 'linked')}
              >
                Inserir linked
              </button>
              <button
                type="button"
                className="btn"
                onClick={() => insertFragmentReference(item.id, 'detached')}
              >
                Inserir detached
              </button>
            </div>
          ) : null}
          <button
            type="button"
            className="btn"
            style={{ marginLeft: 8 }}
            onClick={() => void showUsage(item.id)}
          >
            Ver uso
          </button>
        </div>
      ))}
      {usage ? <p className="muted">{usage}</p> : null}
      {fragment ? (
        <button
          className="btn primary"
          type="button"
          onClick={() => void publishFragment()}
        >
          Publicar fragmento
        </button>
      ) : null}
      {!fragments.length ? (
        <p className="muted">Nenhum fragmento ainda.</p>
      ) : null}
    </div>
  );
}
