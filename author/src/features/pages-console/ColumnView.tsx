import { useEffect, useRef, type KeyboardEvent } from 'react';
import type { PageNode } from '../../types/content';

type Column = {
  parent: string;
  nodes: PageNode[];
  loading: boolean;
  error: string | null;
  hasMore: boolean;
};

type Props = {
  columns: Column[];
  selectedPath: string | null;
  activeColumn: number;
  onSelect: (columnIndex: number, node: PageNode) => void;
  onOpen: (node: PageNode) => void;
  onLoadMore: (parent: string) => void;
  onBackLevel: () => void;
};

function selectedInColumn(
  columnIndex: number,
  columns: Column[],
  selectedPath: string | null,
): string | null {
  const drilled = columns[columnIndex + 1]?.parent;
  const nodes = columns[columnIndex]?.nodes ?? [];
  if (drilled && nodes.some((node) => node.path === drilled)) {
    return drilled;
  }
  if (selectedPath && nodes.some((node) => node.path === selectedPath)) {
    return selectedPath;
  }
  return null;
}

export default function ColumnView({
  columns,
  selectedPath,
  activeColumn,
  onSelect,
  onOpen,
  onLoadMore,
  onBackLevel,
}: Props) {
  const scroller = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const el = scroller.current;
    if (!el) return;
    el.scrollTo({ left: el.scrollWidth, behavior: 'smooth' });
  }, [columns.length]);

  function onKeyDown(
    event: KeyboardEvent<HTMLDivElement>,
    columnIndex: number,
    nodes: PageNode[],
  ) {
    const currentPath = selectedInColumn(columnIndex, columns, selectedPath);
    const index = nodes.findIndex((node) => node.path === currentPath);
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      const nextIndex = event.key === 'ArrowDown'
        ? Math.min(nodes.length - 1, Math.max(0, index + 1))
        : Math.max(0, index <= 0 ? 0 : index - 1);
      const next = nodes[nextIndex];
      if (next) onSelect(columnIndex, next);
    }
    if (event.key === 'ArrowRight') {
      const node = nodes[index];
      if (node?.hasChildren) {
        event.preventDefault();
        onSelect(columnIndex, node);
        const nextColumn = scroller.current?.querySelector<HTMLElement>(
          `[data-column-index="${columnIndex + 1}"] [aria-selected="true"], [data-column-index="${columnIndex + 1}"] [role="treeitem"]`,
        );
        nextColumn?.focus();
      }
    }
    if (event.key === 'ArrowLeft' && columnIndex > 0) {
      event.preventDefault();
      onBackLevel();
      const previous = scroller.current?.querySelector<HTMLElement>(
        `[data-column-index="${columnIndex - 1}"] [aria-selected="true"]`,
      );
      previous?.focus();
    }
    if (event.key === 'Enter') {
      const node = nodes[index];
      if (node?.pageId) {
        event.preventDefault();
        onOpen(node);
      }
    }
  }

  return (
    <div className="column-view" ref={scroller}>
      <div className="column-mobile-back">
        {columns.length > 1 ? (
          <button type="button" className="btn" onClick={onBackLevel}>
            Voltar um nível
          </button>
        ) : null}
      </div>
      <div role="tree" aria-label="Estrutura de páginas" className="column-tree">
        {columns.map((column, columnIndex) => {
          const selected = selectedInColumn(columnIndex, columns, selectedPath);
          const isCurrent = columnIndex === columns.length - 1;
          return (
            <div
              key={`${columnIndex}:${column.parent || '/'}`}
              className={`column ${isCurrent ? 'is-current' : ''} ${columnIndex === activeColumn ? 'is-active' : ''}`}
              data-column-index={columnIndex}
              role="group"
              aria-label={column.parent ? `Filhos de ${column.parent}` : 'Raiz do site'}
            >
              <div className="column-heading">
                {column.parent ? column.parent : 'Site'}
              </div>
              {column.loading ? <p className="column-status">Carregando…</p> : null}
              {column.error ? <p className="column-status error">{column.error}</p> : null}
              {!column.loading && !column.nodes.length ? (
                <p className="column-status">Nenhuma página neste nível.</p>
              ) : null}
              <div
                role="presentation"
                onKeyDown={(event) => onKeyDown(event, columnIndex, column.nodes)}
              >
                {column.nodes.map((node) => {
                  const isSelected = node.path === selected;
                  const label = `${node.kind === 'folder' ? 'Pasta' : 'Página'} ${node.title}`;
                  return (
                    <button
                      key={node.path}
                      type="button"
                      role="treeitem"
                      aria-selected={isSelected}
                      aria-expanded={node.hasChildren ? isSelected : undefined}
                      aria-label={label}
                      title={`${node.title} · ${node.path}`}
                      className={`console-item ${isSelected ? 'is-selected' : ''}`}
                      tabIndex={isSelected || (!selected && columnIndex === activeColumn && node === column.nodes[0]) ? 0 : -1}
                      onClick={() => onSelect(columnIndex, node)}
                      onDoubleClick={() => {
                        if (node.pageId) onOpen(node);
                      }}
                    >
                      <span className={`console-icon ${node.kind}`} aria-hidden="true" />
                      <span className="console-item-text">
                        <strong>{node.title}</strong>
                        <small>{node.path}</small>
                      </span>
                      {node.status ? (
                        <span className={`console-status ${node.status}`}>{node.status}</span>
                      ) : null}
                      {node.hasChildren ? <span className="console-chevron" aria-hidden="true">›</span> : null}
                    </button>
                  );
                })}
              </div>
              {column.hasMore ? (
                <button
                  type="button"
                  className="btn column-more"
                  onClick={() => onLoadMore(column.parent)}
                >
                  Carregar mais
                </button>
              ) : null}
            </div>
          );
        })}
      </div>
    </div>
  );
}
