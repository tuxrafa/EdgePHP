# EDS Compatibility Report

| Field | Value |
|---|---|
| CMS | EdgePHP CMS (provisional) |
| CMS version | 0.1.0-slice1 |
| aem.js | Vendored from `adobe/aem-boilerplate` (see hash below). Upstream aem-lib lineage ~3.1.x (copyright 2026). No local edits. |
| Boilerplate commit | `6b05abfbe0e47df31ea40d44b57dff45964d16b5` (2026-11-20) |
| Date tested | 2026-11-20 (America/Sao_Paulo) |
| Status | Slice 1 PASS — Columns golden path |

## Vendor file hashes (must not change)

| File | SHA-256 |
|---|---|
| `blocks/columns/columns.js` | `d4aa3133fa918126152fec139e17e39c86bb7bd7b99d5ec9d20f66498f7a60e4` |
| `blocks/columns/columns.css` | `dafa4c970b3e056e7293ba15c86f6f7228c0375476faca6befee57191310098b` |
| `scripts/aem.js` | `786e8019b14b4c0bee9df38a3162f129c04310d9d00e7a6dd326c7e5830c84ae` |

Integrity is enforced by `tests/Compatibility/BlockVendorIntegrityTest.php`.

## Blocks

| Block | Profile | Status |
|---|---|---|
| columns | EDS-C1 | PASS |
| hero | EDS-C1 | NOT TESTED |
| fragment | EDS-C2 | NOT TESTED |

## Features

| Feature | Status |
|---|---|
| sections (basic wrapper) | PASS (unit/feature markup) |
| metadata (title) | PARTIAL (title only in slice 1) |
| auto-blocking | NOT IMPLEMENTED (scripts.js keeps hook; no fixture) |
| plain.html | NOT IMPLEMENTED |
| query-index | NOT IMPLEMENTED |

## scripts.js deltas (CMS)

Compared to boilerplate `scripts/scripts.js` at the commit above:

1. **Skip header/footer load** when `<header>` / `<footer>` elements are absent (slice-1 pages omit them to avoid undeclared header/footer/fragment dependency chain).
2. **Fonts load soft-fail** — missing `fonts.css` must not throw.
3. No changes to `decorateMain` order: icons → auto-blocks → sections → blocks → buttons.

All other EDS behavior remains in vendored `aem.js`.

## Never mark PASS without a test

This file must only say PASS when a corresponding automated test is green.
