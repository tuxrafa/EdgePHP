import { useMemo, useState } from 'react';
import { useQueries, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import { api } from '../../services/api';
import type { PageNode } from '../../types/content';
import { useEditorStore } from '../../stores/editorStore';
import ColumnView from './ColumnView';
import { readConsoleNav, writeConsoleNav } from './consoleNav';

const PAGE_SIZE = 200;

export default function PagesConsole() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const user = useEditorStore((s) => s.user);
  const initial = useMemo(() => readConsoleNav(), []);
  const [parents, setParents] = useState<string[]>(initial.parents);
  const [selectedPath, setSelectedPath] = useState<string | null>(initial.selectedPath);
  const [activeColumn, setActiveColumn] = useState(Math.max(0, initial.parents.length - 1));
  const [limits, setLimits] = useState<Record<string, number>>({});

  const templates = useQuery({
    queryKey: ['templates'],
    queryFn: () => api.listTemplates(),
  });

  const results = useQueries({
    queries: parents.map((parent) => {
      const limit = limits[parent] ?? PAGE_SIZE;
      return {
        queryKey: ['page-children', parent, limit],
        queryFn: () => api.listPageChildren(parent, limit, 0),
      };
    }),
  });

  const columnModels = results.map((result, index) => ({
    parent: parents[index] ?? '',
    nodes: result.data?.data ?? [],
    loading: result.isLoading,
    error: result.error instanceof Error ? result.error.message : null,
    hasMore: result.data?.meta.hasMore ?? false,
  }));

  const selected = useMemo(() => {
    if (!selectedPath) return null;
    for (const column of columnModels) {
      const found = column.nodes.find((node) => node.path === selectedPath);
      if (found) return found;
    }
    return null;
  }, [columnModels, selectedPath]);

  const templateName = templates.data?.data.find(
    (template) => template.id === selected?.templateId,
  )?.name;

  function remember(nextParents: string[], nextSelected: string | null) {
    writeConsoleNav({ parents: nextParents, selectedPath: nextSelected });
  }

  function onSelect(columnIndex: number, node: PageNode) {
    const nextParents = parents.slice(0, columnIndex + 1);
    if (node.hasChildren) {
      nextParents.push(node.path);
    }
    setParents(nextParents);
    setSelectedPath(node.path);
    setActiveColumn(node.hasChildren ? columnIndex + 1 : columnIndex);
    remember(nextParents, node.path);
  }

  function onBackLevel() {
    if (parents.length <= 1) return;
    const removed = parents[parents.length - 1] || null;
    const nextParents = parents.slice(0, -1);
    setParents(nextParents);
    setSelectedPath(removed);
    setActiveColumn(nextParents.length - 1);
    remember(nextParents, removed);
  }

  function openEditor(node: PageNode) {
    if (!node.pageId) return;
    remember(parents, node.path);
    navigate(`/editor/${node.pageId}`);
  }

  const crumbs = ['Site'];
  parents.filter(Boolean).forEach((path) => {
    const segment = path.split('/').filter(Boolean).pop();
    if (segment) crumbs.push(segment);
  });
  if (selected && !parents.includes(selected.path)) {
    crumbs.push(selected.segment);
  }

  return (
    <div className="shell console" data-testid="pages-console">
      <header className="topbar">
        <div className="brand">EdgePHP</div>
        <div className="meta">
          <strong>Pages</strong>
          <span>Gerenciador de páginas</span>
        </div>
        <nav className="console-crumbs" aria-label="Caminho">
          {crumbs.map((crumb, index) => (
            <span key={`${crumb}-${index}`}>
              {index > 0 ? <span aria-hidden="true"> / </span> : null}
              {crumb}
            </span>
          ))}
        </nav>
        <div className="spacer" />
        <Link className="btn" to="/">
          Editor
        </Link>
        <span className="badge">{user?.email}</span>
      </header>

      <div className="console-toolbar" role="toolbar" aria-label="Ações da página">
        <button
          type="button"
          className="btn primary"
          data-testid="console-edit"
          disabled={!selected?.pageId}
          onClick={() => selected && openEditor(selected)}
        >
          Edit
        </button>
        {selected?.pageId && selected.status === 'published' ? (
          <a className="btn" href={selected.path} target="_blank" rel="noreferrer">
            Open published
          </a>
        ) : null}
        <button
          type="button"
          className="btn"
          onClick={() => void queryClient.invalidateQueries({ queryKey: ['page-children'] })}
        >
          Refresh
        </button>
      </div>

      <div className="console-body">
        <ColumnView
          columns={columnModels}
          selectedPath={selectedPath}
          activeColumn={activeColumn}
          onSelect={onSelect}
          onOpen={openEditor}
          onLoadMore={(parent) => {
            setLimits((current) => ({
              ...current,
              [parent]: (current[parent] ?? PAGE_SIZE) + PAGE_SIZE,
            }));
          }}
          onBackLevel={onBackLevel}
        />
        <aside className="console-details" aria-live="polite">
          <h2>Propriedades</h2>
          {selected ? (
            <dl>
              <dt>Title</dt>
              <dd>{selected.title}</dd>
              <dt>Path</dt>
              <dd>{selected.path}</dd>
              <dt>Tipo</dt>
              <dd>{selected.kind === 'folder' ? 'Pasta virtual' : 'Página'}</dd>
              <dt>Template</dt>
              <dd>{templateName ?? selected.templateId ?? '—'}</dd>
              <dt>Status</dt>
              <dd>{selected.status ?? '—'}</dd>
              <dt>Last modified</dt>
              <dd>{selected.updatedAt ? new Date(selected.updatedAt).toLocaleString() : '—'}</dd>
            </dl>
          ) : (
            <p className="muted">Selecione uma página ou pasta.</p>
          )}
          {selected?.pageId ? (
            <button type="button" className="btn primary" onClick={() => openEditor(selected)}>
              Edit page
            </button>
          ) : null}
        </aside>
      </div>
    </div>
  );
}
