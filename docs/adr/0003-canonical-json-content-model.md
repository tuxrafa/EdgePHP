# ADR 0003: Canonical JSON content model

## Context

EDS authors historically use Docs/Word. EdgePHP authors via structured JSON that the PHP renderer turns into EDS markup.

## Decision

Persist **canonical structured JSON** (PageRevision.content). HTML is a **render output**, never the source of truth.

## Consequences

- Sanitization and schema validation happen on JSON → HTML.
- Round-trip editing and diffs stay structured.
- Rich text (later) stores Tiptap JSON, converted server-side.

## Alternatives

- Store HTML blobs: easier short-term, breaks sanitization and editor models.
