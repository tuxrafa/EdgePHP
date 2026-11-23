import {
  DndContext,
  KeyboardSensor,
  PointerSensor,
  pointerWithin,
  useDroppable,
  useSensor,
  useSensors,
  type CollisionDetection,
  type DragEndEvent,
} from '@dnd-kit/core';
import {
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import type { BlockNode, ContentNode } from '../../types/content';
import {
  parseSectionDropId,
  sectionDropId,
  useEditorStore,
} from '../../stores/editorStore';

/**
 * Only accept droppables under the pointer. Prefer blocks over section shells so
 * reorders in one section cannot "jump" into a neighbor via closestCenter.
 */
const canvasCollision: CollisionDetection = (args) => {
  const pointerHits = pointerWithin(args);
  if (pointerHits.length === 0) return [];
  const blocks = pointerHits.filter(
    (hit) => !parseSectionDropId(String(hit.id)),
  );
  return blocks.length > 0 ? blocks : pointerHits;
};

function nodeLabel(node: ContentNode): string {
  return node.type;
}

function nodeText(node: ContentNode): string {
  if (
    node.type === 'paragraph' ||
    node.type === 'heading' ||
    node.type === 'link' ||
    node.type === 'button' ||
    node.type === 'quote'
  ) {
    return node.text;
  }
  if (node.type === 'image') return node.alt ?? node.src;
  return '';
}

function SortableBlock({
  sectionId,
  block,
  selected,
  onSelect,
}: {
  sectionId: string;
  block: BlockNode;
  selected: boolean;
  onSelect: () => void;
}) {
  const updateNodeText = useEditorStore((s) => s.updateNodeText);
  const select = useEditorStore((s) => s.select);
  const removeBlock = useEditorStore((s) => s.removeBlock);
  const content = useEditorStore((s) => s.content);
  const moveBlock = useEditorStore((s) => s.moveBlock);
  const sortableId = block.id!;
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: sortableId });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.65 : 1,
    zIndex: isDragging ? 2 : undefined,
  };

  const otherSections = content.sections.filter((s) => s.id !== sectionId);

  return (
    <article
      ref={setNodeRef}
      style={style}
      className={`block-card ${selected ? 'selected' : ''} ${isDragging ? 'dragging' : ''}`}
      data-block-id={sortableId}
      onClick={onSelect}
    >
      <header className="block-card-handle">
        <span
          className="drag-grip"
          {...attributes}
          {...listeners}
          data-testid={`drag-${sortableId}`}
          aria-label={`Arrastar bloco ${block.name}`}
        >
          Arrastar
        </span>
        <span className="block-title">
          block · <strong>{block.name}</strong>
          {block.classes?.length ? ` · ${block.classes.join(' ')}` : ''}
        </span>
        <div className="block-actions" onPointerDown={(e) => e.stopPropagation()}>
          {otherSections.length > 0 ? (
            <label className="move-select">
              <span className="sr-only">Mover para seção</span>
              <select
                aria-label={`Mover ${block.name} para seção`}
                data-testid={`move-${sortableId}`}
                defaultValue=""
                onClick={(e) => e.stopPropagation()}
                onChange={(e) => {
                  const target = e.target.value;
                  e.currentTarget.value = '';
                  if (!target) return;
                  moveBlock(sortableId, sectionDropId(target));
                }}
              >
                <option value="" disabled>
                  Mover…
                </option>
                {content.sections.map((section, index) => (
                  <option
                    key={section.id}
                    value={section.id}
                    disabled={section.id === sectionId}
                  >
                    Section {index + 1}
                  </option>
                ))}
              </select>
            </label>
          ) : null}
          <button
            type="button"
            className="btn danger-btn"
            data-testid={`remove-block-${sortableId}`}
            aria-label={`Remover bloco ${block.name}`}
            onPointerDown={(e) => e.stopPropagation()}
            onClick={(e) => {
              e.preventDefault();
              e.stopPropagation();
              removeBlock(sectionId, sortableId);
            }}
          >
            Remover
          </button>
        </div>
      </header>
      <div className="stack">
        {block.rows.map((row, rowIndex) => (
          <div
            key={row.id ?? rowIndex}
            className="columns-grid"
            style={{
              gridTemplateColumns: `repeat(${Math.max(row.cells.length, 1)}, minmax(0, 1fr))`,
            }}
          >
            {row.cells.map((cell, cellIndex) => (
              <div key={cell.id ?? cellIndex} className="stack">
                {cell.content.map((node, nodeIndex) => (
                  <label key={node.id ?? nodeIndex} className="field">
                    <span style={{ color: 'var(--ink-soft)', fontSize: 11 }}>
                      {nodeLabel(node)}
                    </span>
                    <textarea
                      value={nodeText(node)}
                      onClick={(e) => e.stopPropagation()}
                      onPointerDown={(e) => e.stopPropagation()}
                      onFocus={() =>
                        select({ sectionId, blockId: block.id })
                      }
                      onChange={(e) =>
                        updateNodeText(
                          sectionId,
                          block.id ?? '',
                          rowIndex,
                          cellIndex,
                          nodeIndex,
                          e.target.value,
                        )
                      }
                    />
                  </label>
                ))}
              </div>
            ))}
          </div>
        ))}
      </div>
    </article>
  );
}

function SectionCanvas({
  sectionId,
  title,
  blocks,
  canRemove,
}: {
  sectionId: string;
  title: string;
  blocks: BlockNode[];
  canRemove: boolean;
}) {
  const selected = useEditorStore((s) => s.selected);
  const select = useEditorStore((s) => s.select);
  const insertBlock = useEditorStore((s) => s.insertBlock);
  const removeSection = useEditorStore((s) => s.removeSection);
  const dropId = sectionDropId(sectionId);
  // Empty sections need a droppable shell; non-empty ones accept drops on blocks.
  const { setNodeRef, isOver } = useDroppable({
    id: dropId,
    disabled: blocks.length > 0,
  });
  const itemIds = blocks.map((block) => block.id!).filter(Boolean);

  return (
    <div
      className={`section-card ${selected?.sectionId === sectionId ? 'section-active' : ''} ${isOver ? 'section-drop-over' : ''}`}
      data-testid={`section-${sectionId}`}
      onClick={() => select({ sectionId })}
    >
      <div className="row" style={{ justifyContent: 'space-between' }}>
        <h3>{title}</h3>
        <div className="row">
          <button
            className="btn"
            type="button"
            data-testid={`add-hero-${sectionId}`}
            onClick={(e) => {
              e.stopPropagation();
              select({ sectionId });
              insertBlock('hero', sectionId);
            }}
          >
            + Hero aqui
          </button>
          <button
            className="btn"
            type="button"
            data-testid={`add-columns-${sectionId}`}
            onClick={(e) => {
              e.stopPropagation();
              select({ sectionId });
              insertBlock('columns', sectionId);
            }}
          >
            + Columns aqui
          </button>
          <button
            className="btn danger-btn"
            type="button"
            data-testid={`remove-section-${sectionId}`}
            title={
              canRemove
                ? 'Remover seção'
                : 'Única seção: limpa os blocos ao remover'
            }
            onClick={(e) => {
              e.stopPropagation();
              removeSection(sectionId);
            }}
          >
            Remover seção
          </button>
        </div>
      </div>

      <div
        ref={setNodeRef}
        className={`section-dropzone ${blocks.length === 0 ? 'section-dropzone-empty' : ''}`}
        data-testid={`drop-${sectionId}`}
      >
        <SortableContext items={itemIds} strategy={verticalListSortingStrategy}>
          {blocks.map((block) => (
            <SortableBlock
              key={block.id}
              sectionId={sectionId}
              block={block}
              selected={selected?.blockId === block.id}
              onSelect={() => select({ sectionId, blockId: block.id })}
            />
          ))}
        </SortableContext>

        {!blocks.length ? (
          <p className="muted section-empty-hint">
            Arraste um bloco para cá, ou use a biblioteca / botões acima.
          </p>
        ) : null}
      </div>
    </div>
  );
}

export default function Canvas() {
  const workspace = useEditorStore((s) => s.workspace);
  const page = useEditorStore((s) => s.page);
  const fragment = useEditorStore((s) => s.fragment);
  const content = useEditorStore((s) => s.content);
  const selected = useEditorStore((s) => s.selected);
  const addSection = useEditorStore((s) => s.addSection);
  const insertBlock = useEditorStore((s) => s.insertBlock);
  const moveBlock = useEditorStore((s) => s.moveBlock);
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    }),
  );

  function onDragEnd(event: DragEndEvent) {
    const { active, over } = event;
    if (!over || active.id === over.id) return;
    moveBlock(String(active.id), String(over.id));
    if (document.activeElement instanceof HTMLElement) {
      document.activeElement.blur();
    }
  }

  if (workspace === 'page' && !page) {
    return (
      <div className="section-card">
        <h3>Canvas</h3>
        <p>Crie ou abra uma página no painel Pages para começar.</p>
      </div>
    );
  }

  if (workspace === 'fragment' && !fragment) {
    return (
      <div className="section-card">
        <h3>Canvas</h3>
        <p>Abra um fragmento no painel Fragments.</p>
      </div>
    );
  }

  return (
    <div>
      <div className="row" style={{ marginBottom: 12 }}>
        <button
          className="btn"
          type="button"
          data-testid="add-section"
          onClick={addSection}
        >
          + Section
        </button>
        <button
          className="btn"
          type="button"
          onClick={() => insertBlock('hero', selected?.sectionId)}
        >
          + Hero
        </button>
        <button
          className="btn"
          type="button"
          onClick={() => insertBlock('columns', selected?.sectionId)}
        >
          + Columns
        </button>
        {selected?.sectionId ? (
          <span className="muted">Destino: seção selecionada</span>
        ) : (
          <span className="muted">Destino: última seção</span>
        )}
      </div>

      <DndContext
        id="author-canvas-dnd"
        sensors={sensors}
        collisionDetection={canvasCollision}
        onDragEnd={onDragEnd}
      >
        {content.sections.map((section, index) => {
          const blocks = section.children.filter(
            (c): c is BlockNode => c.type === 'block' && Boolean(c.id),
          );
          return (
            <SectionCanvas
              key={section.id ?? index}
              sectionId={section.id!}
              title={`Section ${index + 1}`}
              blocks={blocks}
              canRemove={content.sections.length > 1}
            />
          );
        })}
      </DndContext>
    </div>
  );
}
