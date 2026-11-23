# ADR 0005: Preserve homeFeed under legacy/

## Context

The git repository already shipped a working homeFeed app. EdgePHP requires an EDS root layout (`blocks/`, `scripts/`, Laravel `app/`).

## Decision

Move the existing application to `legacy/homefeed/` without deleting it. EdgePHP becomes the primary tree at the repository root.

## Consequences

- homeFeed remains recoverable and browsable in git history + legacy path.
- Root README documents EdgePHP as the active product.
- Deploy scripts for homeFeed are not maintained as primary CI.

## Alternatives

- New repository: cleaner, but not the assigned Cloud Agent workspace.
- Subdirectory-only CMS: would break EDS root conventions for developers.
