# EdgePHP CMS

Provisional name for a PHP-backed CMS that preserves the **Adobe AEM Edge Delivery Services (EDS) frontend contract**:

`blocks/<name>/<name>.js|css` + `scripts/aem.js` + `scripts/scripts.js`

PHP replaces the content origin / preview / publish pipeline. Block JS/CSS still run in the browser. Original EDS blocks are not rewritten.

## Status

**Vertical slices:** MongoDB pages → EDS renderer → Author visual → block library → **Editable Templates & Experience Fragments** (composition before render).

See:

- `docs/architecture.md`
- `docs/compatibility.md`
- `docs/content-model.md`
- `docs/templates-fragments.md`
- `docs/adr/`

Seed demo templates/fragments:

```bash
php artisan edgephp:seed-templates
```

## Requirements

- PHP ≥ 8.3 with `ext-mongodb` ≥ 1.21 (or 2.x)
- Composer
- MongoDB
- Node.js (Playwright E2E)
- Redis (reserved; optional for slice 1)

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
# ensure MongoDB is running, then:
php artisan serve
```

Create a columns compatibility page:

```bash
curl -s -X POST http://127.0.0.1:8000/api/v1/pages \
  -H 'Content-Type: application/json' \
  -d @fixtures/eds/columns/page.json
```

Open `/__preview/pages/{id}` or `/columns-compatibility`.

## Tests

```bash
# PHP (Pest)
php artisan test

# Playwright golden test
npm install
npx playwright install chromium
npm run test:e2e:columns
```

## Legacy

The previous **homeFeed** application lives under `legacy/homefeed/` (see ADR 0005).
