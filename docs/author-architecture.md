# Author Visual — Architecture

**Date:** 2026-11-20 (America/Sao_Paulo)  
**Status:** Implementing Author MVP vertical slice  
**Depends on:** Slice 1 renderer + Columns golden path

## Current baseline

Already working:

- Laravel 13 + MongoDB `Page` / `PageRevision`
- `EdsPageRenderer` (same for preview + public)
- Vendored `scripts/aem.js`, `blocks/columns/*` (unmodified)
- Preview: `/__preview/pages/{id}`
- Public: `/{path}`

## Decision: Author is a separate React app

`author/` (Vite + React + TypeScript) is **only** the authoring shell.

Published / preview pages **never** depend on React. They load PHP HTML + EDS `aem.js` / block JS/CSS.

```
Author React  --JSON-->  Laravel APIs  --HTML-->  iframe Preview (/__preview)
                              |
                              +--> Public GET /{path}
```

## Content model (canonical)

Reuse Slice 1 JSON (not Adobe-official):

- `sections[].children[]` where a child may be `block` or default content
- Block: `{ type, name, classes?, rows: [{ cells: [{ content: [] }] }] }`
- Cells (not `columns`) — matches EDS row/cell div nesting and existing PHP renderer

Author UI maps this tree; it does **not** invent a parallel schema.

## Canvas vs Preview (hard split)

| Surface | Role |
|---|---|
| Canvas (React) | Selection, DnD, inline text, structure ops on JSON |
| Preview (iframe) | Real PHP markup + original EDS decoration |

Never run block `decorate()` against React-managed DOM.

## BlockRegistry

Scanner over `blocks/*/`:

- Requires `{name}.js` and/or `{name}.css`
- Optional `_name.json` / `name.json` → stored as `rawModel` + `normalizedModel`
- Emits `BlockDefinition` with `compatibility` hint (EDS-C0/C1 until tested)

Does not rewrite block files.

## Author MVP scope (this increment)

Vertical flow:

1. Login (simple session)
2. List / create pages
3. Open page in Author
4. Insert `columns` from registry
5. Edit cell text
6. Save → MongoDB revision update
7. Preview iframe shows EDS-decorated result

Deferred: full media library, publish UI, Tiptap rich sets, RBAC matrix, autosave polish, keyboard DnD edge cases.

## Compatibility honesty

| Behavior | Status |
|---|---|
| columns decorate + CSS | Proven (Slice 1 golden) |
| Block library discovery | This slice |
| Author DnD → JSON → same renderer | This slice |
| hero / fragment / auto-block | Not claimed |
| component-models.json full inference | Adapter later |

## Incremental plan

1. Docs (this file)  
2. `BlockRegistry` + `GET /api/v1/blocks` + `PATCH /api/v1/pages/{id}` + `GET /api/v1/pages`  
3. Scaffold `author/` SPA served at `/author`  
4. Layout: top / left / canvas / inspector + preview iframe  
5. Insert columns, edit text, save, reload  
6. Pest + Playwright author flow  
7. Demo tunnel link  

## Trade-offs

- Auth is minimal session (sqlite users) while content stays on Mongo — pragmatic hybrid.
- Canvas is structure-first (not pixel-perfect overlay on decorated DOM) to avoid fighting EDS JS.
- Tailwind only inside Author bundle; public CSS remains EDS `styles/` + block CSS.
