# ADR 0006: iframe preview (planned)

## Context

The admin editor must show the real EDS-decorated page, not a React imitation of blocks.

## Decision

Preview uses an iframe pointing at `/__preview/pages/{id}?revision=…`, which runs the same `EdsPageRenderer` as production.

## Consequences

- WYSIWYG fidelity for EDS-C0/C1 blocks.
- Cross-frame DnD is harder; editor shell coordinates via postMessage later.

## Alternatives

- React re-implementations of blocks: rejected (breaks EDS contract).
