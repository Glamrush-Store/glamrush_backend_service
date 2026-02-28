# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full project setup (install deps, copy .env, generate key, migrate, build frontend)
composer setup

# Start dev server (PHP server + queue worker + Vite concurrently)
composer dev

# Run all tests
composer test

# Run a single test file or filter
vendor/bin/pest --filter ProductCatalogMapper

# Lint/format code
vendor/bin/pint

# Generate Swagger/OpenAPI docs
php artisan l5-swagger:generate

# Database
php artisan migrate
php artisan db:seed
```

## Architecture

This is a Laravel 12 application using **Domain-Driven Design** with **Clean Architecture** layers:

**Domain** (`app/Domain/Catalog/{Brand,Category,Product,Vendor}/`) — Pure business logic, no framework dependencies.
- `Entities/` — Rich domain objects with business methods (e.g., `ProductEntity::effectivePrice()`, `isAvailable()`)
- `Contracts/` — Repository interfaces
- `Queries/` — CQRS-style query objects implementing `CacheableQuery` (define their own cache key, tags, TTL)
- `Services/` — Domain services that orchestrate queries through `QueryCache::remember()`
- `DTOs/` — Data transfer objects (legacy pattern, being replaced by Resources)

**Infrastructure** (`app/Infrastructure/`) — External concerns.
- `Persistence/Eloquent/Models/` — Eloquent models (use `HasUlids` for ULID primary keys, `PublishedScope` for auto-filtering)
- `Persistence/Eloquent/Repositories/` — Repository implementations (Eloquent → Domain Entity via mappers)
- `Persistence/Eloquent/Mappers/` — Convert Eloquent models to domain entities (`::toDomain()`)
- `Caching/QueryCache.php` — Cache wrapper using Laravel tagged cache

**Presentation** (`app/Presentation/Http/`) — API layer.
- `Controllers/Catalog/` — Single-action invokable controllers (one endpoint per class)
- `Resources/Catalog/` — Laravel Resources transforming entities to JSON responses

**Bindings**: Repository interfaces are bound to Eloquent implementations in `AppServiceProvider`.

## Request Flow

```
Route → Controller (creates Query) → Service (QueryCache::remember) → Repository (Eloquent) → Mapper::toDomain() → Entity → Resource → JSON
```

## Testing

- **Framework**: Pest 4.x (BDD-style)
- **Test DB**: SQLite in-memory (configured in phpunit.xml)
- **Factories**: `tests/Support/Factories/` — Static factory classes (not Eloquent factories), e.g. `ProductFactory::make()`, `ProductFactory::withRelations()`
- **Lazy loading prevention**: Enabled in `TestCase::setUp()` via `Model::preventLazyLoading()`
- Tests mirror domain structure: `tests/Unit/{Brand,Category,Product}/`

## Key Conventions

- All models use ULIDs (`HasUlids` trait), not auto-incrementing IDs
- Controllers are single-action (invokable `__invoke` method), one per route
- Query objects define caching behavior by implementing `CacheableQuery`
- Product filtering/sorting uses Laravel Purity (`Filterable`, `Sortable` traits) with custom filters in `app/Shared/Filters/`
- API routes are versioned under `/api/v1/` (see `routes/api.php`)
- Swagger annotations use PHP 8 attributes (`#[OA\Schema]`)
- Code style enforced by Laravel Pint
- PostgreSQL is the production database
