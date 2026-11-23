# ADR 0009: Static rendering strategy

## Context

The product wants optional static generation after dynamic rendering is stable.

## Decision

Dynamic `EdsPageRenderer` is the source of truth for preview and production HTML. Static export (`php artisan site:export`) is deferred until slice 8 and must reuse the same renderer.

## Consequences

- No dual markup pipelines.
- Cache keys include site/locale/path/revision/environment.

## Alternatives

- Separate static-only templates: rejected.
