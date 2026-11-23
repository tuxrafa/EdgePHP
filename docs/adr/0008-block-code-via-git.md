# ADR 0008: Block code via Git

## Context

Authors must not upload arbitrary JS into `blocks/`.

## Decision

Block JS/CSS are code assets versioned in Git (EDS code bus). Content authors edit JSON content only. Developers manage `blocks/` via PRs.

## Consequences

- Clear separation of content vs code domains.
- RBAC later: developer role for block registry; authors cannot write to `blocks/`.

## Alternatives

- In-CMS block upload: rejected for MVP security model.
