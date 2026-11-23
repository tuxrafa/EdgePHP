# ADR 0010 — Editable Templates & Experience Fragments

**Date:** 2026-11-21 (America/Sao_Paulo)  
**Status:** Accepted (vertical slice)

## Context

Pages previously stored a flat `{metadata, sections}` document rendered directly by `EdsPageRenderer`. Product needs AEM-inspired Editable Templates and Experience Fragments without inventing Adobe APIs or a second HTML pipeline.

## Decision

1. **Entities:** `templates` / `template_revisions`, `fragments` / `fragment_revisions`, plus materialized `content_references` and immutable `published_releases`.
2. **Composition before render:** `ContentComposer` + `TemplateResolver` + `FragmentResolver` produce an ephemeral `{metadata, sections}` document. `EdsPageRenderer` remains the only HTML emitter.
3. **Page association:** `Page.templateId` + revision `slots` hold page-specific content. Legacy pages without `templateId` keep top-level `sections`.
4. **Fragment modes:** `linked` | `detached` | `pinned` on `fragmentReference` nodes.
5. **Initial content ≠ shared reference:** slot `initialContent` is copied once at page create; linked fragments resolve at compose time.
6. **Preview vs publish:** preview uses draft revisions; publish freezes dependency revision IDs into `published_releases` and serves that snapshot publicly when present.
7. **Author:** Templates / Fragments tabs reuse the Author shell; fragment editing reuses Canvas; template layout is edited as a structure list; page create offers a template selector.

## Consequences

- Public/preview routes always compose (or read a release), never hand raw template docs to the renderer.
- Nested block ban still applies inside slot/fragment section trees.
- Advanced template-switch migration UX and batch fragment fan-out rebuilds are scaffolded via dependency graph + usage APIs; full impact wizards can deepen later without changing the composition contract.
