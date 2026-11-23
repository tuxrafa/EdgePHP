# Templates & Experience Fragments

## Flow

```
Page (+ slots) → Template layout → FragmentResolver → ContentComposer → EdsPageRenderer → HTML → aem.js
```

## Author

- **Pages** — create with optional template; edit slot content in Canvas; Publish page.
- **Templates** — layout of slots + linked fragment references; Publish template.
- **Fragments** — edit like a mini-page; Publish fragment; insert linked/detached into the open page.

Seed demos:

```bash
php artisan edgephp:seed-templates
```

## API

| Method | Path |
|---|---|
| GET/POST | `/api/v1/templates` |
| PATCH | `/api/v1/templates/{id}` |
| POST | `/api/v1/templates/{id}/publish` |
| GET | `/api/v1/templates/{id}/usage` |
| GET | `/__preview/templates/{id}` |
| GET/POST | `/api/v1/fragments` |
| PATCH | `/api/v1/fragments/{id}` |
| POST | `/api/v1/fragments/{id}/publish` |
| GET | `/api/v1/fragments/{id}/usage` |
| GET | `/__preview/fragments/{id}` |
| POST | `/api/v1/pages/{id}/publish` |

Creating a page with `templateId` seeds `slots` from the template `initialContent` (copy-once).
