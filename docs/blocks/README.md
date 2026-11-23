# EdgePHP Block Library (Phase 1)

Blocks live under `blocks/{name}/` with EDS conventions:

- `{name}.js` — `export default function decorate(block)`
- `{name}.css` — scoped to `.blockname`
- `{name}.author.json` — Author manifest (not an Adobe-official format)

## Catalog

| Block | Category | Notes |
|---|---|---|
| heading | content | Semantic title |
| text | content | Paragraphs |
| image | content | Picture + caption |
| button | content | CTA link |
| divider | content | Horizontal rule |
| columns | layout | **Vendor Adobe** JS/CSS (unmodified) |
| grid | layout | Responsive grid |
| hero | presentation | Split / centered / background |
| cards | collections | Card collection |
| cta | presentation | Conversion band |
| accordion | interactive | `details`/`summary` |
| slider | interactive | One slide at a time |
| carousel | interactive | Multi-item strip (`carrousel` alias) |
| gallery | media | Grid + lightbox |
| video | media | YouTube nocookie embed |

## Tokens

Shared design tokens: `styles/tokens.css` (loaded before `styles/styles.css`).

## Compatibility

- `columns` remains Adobe boilerplate (hash-tested).
- New blocks follow EDS DOM-first contracts for this CMS; they are not Adobe originals.
