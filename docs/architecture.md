# EdgePHP CMS — Architecture (Slice 0 → Slice 1)

**Date:** 2026-11-20 (America/Sao_Paulo)  
**Status:** Vertical slice 1 in progress  
**Product (provisional):** EdgePHP CMS

## A. Architecture found in this repository

The repository `tuxrafa/homefeed` previously contained **homeFeed**, a React/Vite + Express (TypeScript/SQLite) personal start page. It had **no** Laravel, MongoDB, EDS `blocks/`, `scripts/aem.js`, or CMS content model.

That application was moved intact to `legacy/homefeed/` so EdgePHP can occupy the EDS-shaped root layout without deleting existing code.

Current target layout (slice 1):

```
/
├── app/Domain|Application|Compatibility|Infrastructure|Http
├── blocks/columns/          # vendor EDS block (unmodified)
├── scripts/aem.js           # vendor (unmodified)
├── scripts/scripts.js       # boilerplate-based, CMS-documented deltas
├── styles/                  # boilerplate styles
├── fixtures/eds/            # hashes + page fixtures
├── docs/                    # architecture, compatibility, ADRs
├── legacy/homefeed/         # preserved prior app
└── tests/{Unit,Feature,Compatibility,E2E}
```

## B. Differences vs product prompt

| Prompt expectation | Current reality / decision |
|---|---|
| Greenfield EdgePHP repo | Built inside `homefeed`; prior app preserved under `legacy/` |
| Full DDD tree day one | Only Content + Eds Compatibility for slice 1 |
| React admin + dnd-kit | Deferred (slice 2+) |
| Static export, media, auth, audit | Deferred |
| Laravel 13 | Confirmed (framework 13.x, PHP 8.3+) |
| MongoDB primary store | Configured via `mongodb/laravel-mongodb` 5.12 |
| Unmodified `scripts.js` | Small CMS adaptations (skip header/footer when absent); documented |

## C. EDS research snapshot (mandatory)

Sources consulted (live docs / GitHub, 2026-11-20):

- https://www.aem.live/developer/anatomy-of-a-project
- https://www.aem.live/developer/markup-sections-blocks
- https://www.aem.live/docs/architecture
- https://github.com/adobe/aem-boilerplate (`main` @ `6b05abfbe0e47df31ea40d44b57dff45964d16b5`)
- https://github.com/adobe/aem-lib CHANGELOG (aem.js lineage through 3.1.9)

Contract preserved:

`CONTENT MODEL → SERVER MARKUP → aem.js/scripts.js → DECORATED DOM → block JS/CSS`

Block markup (server): nested `div` rows/cells; class = block name.  
Decoration (browser): `decorateSections` → `decorateBlocks` → `loadBlock` sets `data-block-status="loaded"`.  
`aem.js` is vendored — never edited for local fixes.

## D. Risks

1. **Boilerplate `scripts.js` loads header/footer/fragments** — can 404 and fail golden tests; mitigated by guarded load + omitting header/footer in slice-1 pages.
2. **RUM / Trusted Types / CSP** from Adobe `head.html` may break local Playwright; slice 1 uses semantic head without enforcing Adobe CSP.
3. **MongoDB not on default Ubuntu image** — requires daemon + pecl `ext-mongodb` ≥ 1.21.
4. **homeFeed coexistence** — accidental deletes; legacy path + ADR.
5. **Falsely claiming EDS-C2+** — only mark PASS with tests.

## E. Vertical slice 1 plan

**Goal:** Store a page in MongoDB with an original Columns block; render EDS-compatible markup; prove browser decoration with Playwright; fail if columns JS/CSS hashes change.

1. Bootstrap Laravel 13 + MongoDB connection  
2. `Page` + `PageRevision` Mongo models  
3. `POST/GET /api/v1/pages`  
4. `EdsPageRenderer` / `EdsSectionRenderer` / `EdsBlockRenderer`  
5. Public/preview HTML routes  
6. Serve `/blocks`, `/scripts`, `/styles`  
7. Import unmodified Columns + vendored `aem.js`  
8. Pest markup + API tests  
9. Playwright Compatibility Golden Test  
10. Docs: `compatibility.md` + ADRs  

**Explicitly out of scope:** visual editor, dnd-kit, Tiptap, publish/rollback, media, static export.

## F. Files to create / modify (slice 1)

**Create:** domain models, Eds renderers, API controllers, routes, EDS asset trees, fixtures, Pest + Playwright tests, docs/ADRs.  
**Modify:** `config/database.php`, `.env*`, `bootstrap/app.php`, `composer.json`, `phpunit.xml`, `.gitignore`, root `README.md`, `package.json` (Playwright).  
**Do not modify:** `blocks/columns/columns.js`, `blocks/columns/columns.css`, `scripts/aem.js`.

## G. Tests to create (slice 1)

| Test | Purpose |
|---|---|
| `tests/Unit/Compatibility/EdsBlockRendererTest.php` | Deterministic columns markup |
| `tests/Feature/Api/CreatePageTest.php` | POST/GET page persistence |
| `tests/Feature/Render/PageRenderTest.php` | HTTP 200 + pre-JS markup |
| `tests/Compatibility/BlockVendorIntegrityTest.php` | SHA-256 of original columns/aem files |
| `tests/E2E/columns-golden.spec.ts` | Playwright golden: loaded block, no JS errors |

## H. Compatibility targets

- MVP: **EDS-C0 / EDS-C1** for `columns`  
- Do not claim C2+ until tested
