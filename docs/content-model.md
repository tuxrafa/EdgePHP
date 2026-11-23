# Content Model (Slice 1)

Canonical authoring source of truth is **structured JSON**, not HTML.

## Page

```json
{
  "id": "ULID",
  "siteId": "main",
  "locale": "pt-BR",
  "path": "/blog/post",
  "title": "Post",
  "status": "draft",
  "draftRevisionId": "ULID",
  "publishedRevisionId": null,
  "metadata": {},
  "createdAt": "...",
  "updatedAt": "..."
}
```

## PageRevision

```json
{
  "id": "ULID",
  "pageId": "ULID",
  "number": 1,
  "content": {
    "metadata": { "title": "..." },
    "sections": []
  },
  "authorId": null,
  "message": null,
  "createdAt": "..."
}
```

## Section / Block (slice 1)

See product prompt. Nested blocks are rejected. Slice 1 renderer supports:

- `section` with `children`
- `block` with `name`, optional `classes`, `rows[].cells[].content[]`
- default content nodes: `paragraph` (`text`), `heading` (`level`, `text`)

HTML is never the persistence format; the EDS renderer emits semantic markup for the browser runtime.
