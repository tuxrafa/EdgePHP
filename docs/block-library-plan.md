# Block Library Plan — EdgePHP CMS

**Date:** 2026-11-20 (America/Sao_Paulo)  
**Status:** Phase 1 implementation

## Inventory (before this work)

| Asset | Status |
|---|---|
| `blocks/columns/` (Adobe boilerplate JS/CSS) | Present, vendor — do not modify |
| BlockRegistry (`blocks/*/`) | Present — extend for `.author.json` |
| EdsPageRenderer / Block / Section / DefaultContent | Present — extend content node types |
| Author React (`/author`) | Present — add categories + manifest defaults |
| Design tokens | Missing → `styles/tokens.css` |
| Hero, cards, slider, carousel, accordion, … | Missing |

## EDS conventions preserved

- `blocks/{name}/{name}.js` + `.css`
- `export default function decorate(block)`
- Server emits nested row/cell `div`s; browser decorates
- Our manifests: `{name}.author.json` (internal Author schema, not Adobe-official)
- Optional EDS models `_name.json` / `name.json` still discovered as `rawModel`

## Adapter notes

| Concern | Approach |
|---|---|
| Author fields ↔ DOM | `initialStructure` in `.author.json` → JSON rows/cells → PHP HTML |
| Vendor columns | Untouched JS/CSS; only add `columns.author.json` |
| Media | Image nodes use `/media/...` paths; Media Library later |
| Auto-blocking | Not reimplemented for new blocks; explicit blocks only |

## Phase 1 delivery (this increment)

heading, text, image, button, divider, columns(+manifest), grid, hero, cards, cta, accordion, slider, carousel, gallery, video

## Later phases

Header/footer globals, forms, blog listings, pricing, map, etc. — see product prompt categories F–I.

## Compatibility honesty

- columns: EDS-C1 (golden tested)
- New EdgePHP blocks: EDS-C0/C1 style contracts, **CMS-authored**; not claimed as Adobe originals
- C2+ (fragments, plain.html feeds): not in Phase 1
