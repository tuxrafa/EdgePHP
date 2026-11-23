# ADR 0001: Laravel vs Symfony

## Context

EdgePHP needs a PHP ≥ 8.3 backend with Composer, MongoDB integration, Redis, queues, and a clear HTTP/API layer.

## Decision

Use **Laravel 13** (current stable major requiring PHP ^8.3).

## Consequences

- Faster scaffolding for API, config, testing (Pest), artisan commands.
- Official `mongodb/laravel-mongodb` supports Laravel 12/13.
- Team must follow Laravel conventions at HTTP boundaries while keeping Domain/Compatibility code framework-light.

## Alternatives

- **Symfony**: excellent for long-lived CMS cores; heavier bootstrap for slice 1.
- **Custom PHP**: rejected — reinventing routing/DI/testing.
