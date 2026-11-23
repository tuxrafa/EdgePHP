# ADR 0007: No nested blocks

## Context

EDS documentation advises against nesting blocks for authorability.

## Decision

Reject nested `type: block` nodes in content validation (slice 1 enforced in `CreatePage`).

## Consequences

- Simpler editor and renderer.
- Complex layouts use sections + sibling blocks or auto-blocking later.

## Alternatives

- Allow nesting with adapters: deferred; would raise compatibility cost.
