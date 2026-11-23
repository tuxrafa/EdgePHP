# ADR 0004: Keep aem.js as vendor

## Context

EDS block loading and decoration live in `scripts/aem.js`. Forking it creates permanent drift from Adobe boilerplate upgrades.

## Decision

Treat `scripts/aem.js` as **vendor**. Never modify it to fix CMS issues. Extensions go in `scripts/scripts.js` or `scripts/cms-compat.js`. Track boilerplate commit + file hashes in `docs/compatibility.md`.

## Consequences

- Upgrade path: bump vendor file, run compatibility suite, update hashes/docs.
- Incompatibilities are classified and adapted outside aem.js.

## Alternatives

- Fork aem.js: rejected.
- Reimplement loader in PHP: rejected — breaks EDS frontend contract.
