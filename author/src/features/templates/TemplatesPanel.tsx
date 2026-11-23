import { useEffect, useState } from 'react';
import { useEditorStore } from '../../stores/editorStore';
import type { TemplateLayoutNode } from '../../types/content';
import { uid } from '../../lib/ids';

export default function TemplatesPanel() {
  const templates = useEditorStore((s) => s.templates);
  const template = useEditorStore((s) => s.template);
  const templateLayout = useEditorStore((s) => s.templateLayout);
  const fragments = useEditorStore((s) => s.fragments);
  const loadTemplates = useEditorStore((s) => s.loadTemplates);
  const loadFragments = useEditorStore((s) => s.loadFragments);
  const openTemplate = useEditorStore((s) => s.openTemplate);
  const createTemplate = useEditorStore((s) => s.createTemplate);
  const setTemplateLayout = useEditorStore((s) => s.setTemplateLayout);
  const publishTemplate = useEditorStore((s) => s.publishTemplate);
  const [name, setName] = useState('Landing');
  const [busy, setBusy] = useState(false);
  const [usage, setUsage] = useState('');

  useEffect(() => {
    void loadTemplates();
    void loadFragments();
  }, [loadTemplates, loadFragments]);

  async function onCreate() {
    setBusy(true);
    try {
      await createTemplate(name.trim() || 'Template');
    } finally {
      setBusy(false);
    }
  }

  function addSlot() {
    const next: TemplateLayoutNode[] = [
      ...templateLayout,
      {
        type: 'slot',
        id: `slot-${uid('x').slice(-6)}`,
        region: 'main',
        label: 'Nova área editável',
        editable: true,
        required: false,
        allowedBlocks: [],
        allowSections: true,
        allowFragments: true,
      },
    ];
    setTemplateLayout(next);
  }

  function addFragmentRef(fragmentId: string) {
    const next: TemplateLayoutNode[] = [
      ...templateLayout,
      {
        type: 'fragmentReference',
        id: `ref-${uid('x').slice(-6)}`,
        region: 'main',
        fragmentId,
        variation: 'default',
        mode: 'linked',
      },
    ];
    setTemplateLayout(next);
  }

  function removeNode(index: number) {
    setTemplateLayout(templateLayout.filter((_, i) => i !== index));
  }

  function moveNode(index: number, dir: -1 | 1) {
    const target = index + dir;
    if (target < 0 || target >= templateLayout.length) return;
    const next = templateLayout.slice();
    const [item] = next.splice(index, 1);
    next.splice(target, 0, item);
    setTemplateLayout(next);
  }

  return (
    <div className="stack">
      <div className="field">
        <label htmlFor="tpl-name">Novo template</label>
        <input
          id="tpl-name"
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
        Criar template
      </button>
      <hr style={{ borderColor: 'rgba(255,255,255,0.08)', width: '100%' }} />
      {templates.map((item) => (
        <button
          key={item.id}
          type="button"
          className={`list-item ${template?.id === item.id ? 'active' : ''}`}
          onClick={() => void openTemplate(item.id)}
        >
          {item.name}
          <small>
            {item.slug} · {item.status}
            {item.publishedRevisionId ? ' · published' : ' · draft'}
          </small>
        </button>
      ))}
      {!templates.length ? (
        <p className="muted">Nenhum template. Rode `php artisan edgephp:seed-templates`.</p>
      ) : null}

      {template ? (
        <div className="stack" style={{ marginTop: 12 }}>
          <strong style={{ fontSize: 12, letterSpacing: '0.06em' }}>
            LAYOUT · {template.name}
          </strong>
          <p className="muted">
            Slots = áreas editáveis da página. Fragment refs = conteúdo
            compartilhado (linked).
          </p>
          {templateLayout.map((node, index) => (
            <div key={`${node.type}-${index}`} className="list-item active">
              {node.type === 'slot' ? (
                <>
                  Slot · <strong>{node.id}</strong>
                  <small>{node.label ?? 'Área editável'}</small>
                </>
              ) : node.type === 'fragmentReference' ? (
                <>
                  Fragment · <strong>{node.fragmentId.slice(0, 8)}…</strong>
                  <small>
                    mode={node.mode ?? 'linked'} · {node.region ?? 'main'}
                  </small>
                </>
              ) : (
                <>Section fixa</>
              )}
              <div className="row" style={{ marginTop: 6, gap: 4 }}>
                <button
                  type="button"
                  className="btn"
                  onClick={() => moveNode(index, -1)}
                >
                  ↑
                </button>
                <button
                  type="button"
                  className="btn"
                  onClick={() => moveNode(index, 1)}
                >
                  ↓
                </button>
                <button
                  type="button"
                  className="btn danger-btn"
                  onClick={() => removeNode(index)}
                >
                  Remover
                </button>
              </div>
            </div>
          ))}
          <button className="btn" type="button" onClick={addSlot}>
            + Slot editável
          </button>
          <div className="field">
            <label htmlFor="add-frag">+ Fragmento linked</label>
            <select
              id="add-frag"
              defaultValue=""
              onChange={(e) => {
                const id = e.target.value;
                e.currentTarget.value = '';
                if (id) addFragmentRef(id);
              }}
            >
              <option value="" disabled>
                Escolher fragmento…
              </option>
              {fragments.map((f) => (
                <option key={f.id} value={f.id}>
                  {f.name}
                </option>
              ))}
            </select>
          </div>
          <button
            className="btn primary"
            type="button"
            onClick={() => void publishTemplate().then(() => setUsage('Publicado'))}
          >
            Publicar template
          </button>
          {usage ? <p className="muted">{usage}</p> : null}
        </div>
      ) : null}
    </div>
  );
}
