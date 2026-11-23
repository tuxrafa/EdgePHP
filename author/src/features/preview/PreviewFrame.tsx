import { useEditorStore } from '../../stores/editorStore';

export default function PreviewFrame() {
  const workspace = useEditorStore((s) => s.workspace);
  const page = useEditorStore((s) => s.page);
  const template = useEditorStore((s) => s.template);
  const fragment = useEditorStore((s) => s.fragment);
  const previewKey = useEditorStore((s) => s.previewKey);
  const dirty = useEditorStore((s) => s.dirty);

  let src: string | null = null;
  if (workspace === 'page' && page) {
    src = `/__preview/pages/${page.id}?revision=${page.draftRevisionId ?? ''}&t=${previewKey}`;
  } else if (workspace === 'template' && template) {
    src = `/__preview/templates/${template.id}?t=${previewKey}`;
  } else if (workspace === 'fragment' && fragment) {
    src = `/__preview/fragments/${fragment.id}?t=${previewKey}`;
  }

  if (!src) {
    return (
      <div className="muted">
        Abra uma página, template ou fragmento para ver o preview.
      </div>
    );
  }

  return (
    <>
      {dirty ? (
        <div className="muted">
          Há alterações não salvas — o iframe mostra a última revisão persistida.
          Clique em Save para atualizar.
        </div>
      ) : null}
      <iframe
        key={previewKey}
        className="preview-frame"
        title="EDS Preview"
        src={src}
      />
    </>
  );
}
