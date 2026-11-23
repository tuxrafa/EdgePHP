import { sectionDropId, useEditorStore } from '../../stores/editorStore';

export default function StructurePanel() {
  const content = useEditorStore((s) => s.content);
  const selected = useEditorStore((s) => s.selected);
  const select = useEditorStore((s) => s.select);
  const addSection = useEditorStore((s) => s.addSection);
  const removeSection = useEditorStore((s) => s.removeSection);
  const removeBlock = useEditorStore((s) => s.removeBlock);
  const moveBlock = useEditorStore((s) => s.moveBlock);

  return (
    <div className="stack">
      <button className="btn" type="button" onClick={addSection}>
        + Section
      </button>
      {content.sections.map((section, sIndex) => (
        <div key={section.id ?? sIndex} className="stack">
          <div className="row" style={{ gap: 6 }}>
            <button
              type="button"
              className={`list-item ${selected?.sectionId === section.id ? 'active' : ''}`}
              style={{ flex: 1 }}
              onClick={() => select({ sectionId: section.id })}
            >
              Section {sIndex + 1}
              <small>{section.children.length} children</small>
            </button>
            <button
              type="button"
              className="btn danger-btn"
              aria-label={`Remover section ${sIndex + 1}`}
              onClick={() => section.id && removeSection(section.id)}
            >
              ×
            </button>
          </div>
          {section.children.map((child, cIndex) => {
            if (child.type === 'fragmentReference') {
              return (
                <div
                  key={child.id ?? cIndex}
                  className="list-item"
                  style={{ marginLeft: 12 }}
                >
                  ⧉ fragment · {child.mode ?? 'linked'} ·{' '}
                  {child.fragmentId.slice(0, 8)}…
                </div>
              );
            }
            if (child.type !== 'block' || !child.id) return null;
            return (
              <div
                key={child.id ?? cIndex}
                className="row"
                style={{ marginLeft: 12, gap: 6 }}
              >
                <button
                  type="button"
                  className={`list-item ${selected?.blockId === child.id ? 'active' : ''}`}
                  style={{ flex: 1 }}
                  onClick={() =>
                    select({ sectionId: section.id, blockId: child.id })
                  }
                >
                  ▸ {child.name}
                </button>
                {content.sections.length > 1 ? (
                  <select
                    aria-label={`Mover ${child.name}`}
                    defaultValue=""
                    style={{ maxWidth: 88 }}
                    onChange={(e) => {
                      const target = e.target.value;
                      e.currentTarget.value = '';
                      if (target && child.id) {
                        moveBlock(child.id, sectionDropId(target));
                      }
                    }}
                  >
                    <option value="" disabled>
                      →
                    </option>
                    {content.sections.map((s, i) => (
                      <option
                        key={s.id}
                        value={s.id}
                        disabled={s.id === section.id}
                      >
                        S{i + 1}
                      </option>
                    ))}
                  </select>
                ) : null}
                <button
                  type="button"
                  className="btn danger-btn"
                  aria-label={`Remover ${child.name}`}
                  onClick={() =>
                    section.id && child.id && removeBlock(section.id, child.id)
                  }
                >
                  ×
                </button>
              </div>
            );
          })}
        </div>
      ))}
    </div>
  );
}
