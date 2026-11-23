# ADR 0002: MongoDB as primary datastore

## Context

Page revisions store nested JSON content trees (sections/blocks/cells). Document storage maps naturally.

## Decision

Use **MongoDB** via **`mongodb/laravel-mongodb`** as the primary content store for Page and PageRevision.

## Consequences

- Flexible revision documents without rigid SQL migrations for every content field.
- Requires `ext-mongodb` ^1.21|^2 and a running MongoDB for feature/E2E tests.
- Relational concerns (auth users) may later use hybrid connections; slice 1 keeps content on Mongo.

## Alternatives

- PostgreSQL JSONB: strong alternative; deferred to keep prompt alignment.
- Filesystem JSON: insufficient for concurrent edits/query.
