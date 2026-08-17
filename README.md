# Glamrush Backend Service

The customer-facing commerce API for Glamrush storefronts. This Laravel service provides storefront catalog delivery, customer authentication, guest and authenticated carts, checkout, discounts, orders, payments, saved items, addresses, content, newsletter subscriptions, and contact submissions.

## Platform context

| Repository | Responsibility |
| --- | --- |
| `glamrush_admin_service` | Admin API, catalog ownership, merchandising, configuration, and operations |
| `glamrush-admin` | Nuxt administration interface |
| `glamrush_backend_service` | Public and customer-authenticated commerce API |
| `glamrush_storefront` | Nuxt customer storefront |

The two Laravel APIs currently share PostgreSQL. The Admin Service owns catalog schema and writes; this service reads catalog data and owns storefront commerce workflows. Redis backs caching, rate limiting, queues, idempotency coordination, and cache metrics in the recommended configuration.

## Core capabilities

- Storefront-scoped homepage, categories, catalog, search, filters, facets, and product details
- Multi-category product support and variant-aware availability
- Guest carts using `X-Cart-Token`, authenticated carts, and post-login cart merging
- Sanctum stateful SPA authentication, Google social login, and password recovery
- Saved items, customer addresses, location options, and order history
- Shipping quotes, discount validation, checkout, inventory reservation, and failed-order cart restoration
- Paystack and Flutterwave initialization, verification, callbacks, and webhooks
- Order/payment idempotency and transactional outbox delivery
- Public content pages, FAQs, contact submissions, and newsletter lifecycle
- Configurable rate limits, response caching, cache invalidation, and Sentry support
- Swappable Laravel, RabbitMQ, or Kafka domain-event transport

See [Feature catalog](docs/features.md) for details.

## Technology

- PHP 8.2+
- Laravel 12 and Laravel Sanctum
- PostgreSQL and Redis
- Laravel Octane
- Paystack and Flutterwave
- Resend-compatible mail delivery
- Sentry
- RabbitMQ or Kafka as optional event transports
- Pest 4

## Local installation

### Prerequisites

- PHP 8.2+ and Composer 2
- PostgreSQL
- Redis with the PHP Redis extension
- Node.js 20+ and npm for Vite assets
- Optional RabbitMQ or Kafka when testing a non-Laravel event driver

### Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

On PowerShell:

```powershell
Copy-Item .env.example .env
```

Configure PostgreSQL, Redis, storefront origins, sessions, mail, payments, storage, and event transport in `.env`.

This service expects Admin-owned catalog tables and seed data to exist. In the current shared-database setup, migrate and seed the Admin Service first, then run:

```bash
php artisan migrate
php artisan db:seed
```

Backend migrations must not recreate Admin-owned catalog tables such as `products`, `product_variants`, `categories`, or `category_product`.

### Run locally

```bash
php artisan serve --port=8000
php artisan queue:work --queue=glamrush-storefront,default
php artisan schedule:work
npm run dev
```

Run each long-lived command in a separate terminal. The Storefront defaults to this API at `http://localhost:8000/api/v1`.

If `EVENT_BUS_DRIVER` is `rabbitmq` or `kafka`, also run:

```bash
php artisan events:consume
```

With the default `laravel` driver, no external event consumer is required. The scheduler dispatches transactional outbox messages and expires pending orders every minute.

## Useful commands

```bash
composer test
./vendor/bin/pint
php artisan route:list
php artisan l5-swagger:generate
php artisan outbox:dispatch
php artisan orders:expire-pending
php artisan optimize:clear
```

## Configuration groups

| Group | Important variables |
| --- | --- |
| SPA security | `SANCTUM_STATEFUL_DOMAINS`, `CORS_ALLOWED_ORIGINS`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE` |
| Database and Redis | `DB_*`, `REDIS_*`, `CACHE_STORE`, `CACHE_LIMITER`, `QUEUE_CONNECTION` |
| Event delivery | `EVENT_BUS_DRIVER`, `RABBITMQ_*`, `KAFKA_*` |
| API protection | `API_RATE_LIMIT_*` |
| Caching | `STOREFRONT_*_CACHE_TTL`, `PAYMENT_METHODS_CACHE_TTL`, `SHIPPING_CACHE_TTL`, `PUBLIC_HTTP_CACHE_*` |
| Payments | `PAYSTACK_*`, `FLUTTERWAVE_*` |
| Mail | `MAIL_*`, `RESEND_API_KEY`, notification settings stored by Admin |
| Idempotency | `IDEMPOTENCY_LOCK_SECONDS`, `IDEMPOTENCY_WAIT_SECONDS` |
| Monitoring | `SENTRY_*`, `CACHE_METRICS_*`, Laravel logging variables |

Many runtime values can be supplied through Admin-managed settings. Environment values remain fallback/bootstrap configuration. Never commit `.env`, provider secrets, service-account material, webhook secrets, or customer data.

## Authentication and request identity

The first-party Storefront uses Sanctum's HttpOnly stateful SPA flow. It requests `/sanctum/csrf-cookie`, sends credentials with every request, and includes `X-XSRF-TOKEN` on mutations. Customer bearer tokens are not required for the first-party SPA.

Guest carts are identified by `X-Cart-Token`. After authentication the Storefront calls the cart merge endpoint, then discards the guest token only after a successful merge.

Checkout and payment initialization require an `Idempotency-Key`. Payment webhooks must pass provider signature verification in addition to rate limiting.

## API documentation

- Base URL: `/api/v1`
- Storefront base: `/api/v1/storefronts/{storefront}`
- Postman: `glamrush-api.postman_collection.json`
- [Storefront API](docs/storefront-api.md)
- [Catalog filters](docs/product-catalog-filters.md)
- OpenAPI definitions are generated through L5 Swagger

## Documentation

- [Architecture](docs/architecture.md)
- [Feature catalog](docs/features.md)
- [Cloudflare R2 storage](docs/cloudflare-r2-storage.md)
- [Order and payment idempotency](docs/order-payment-idempotency.md)
- [Event bus](docs/event-bus.md)
- [Shared cache versioning](docs/shared-cache-versioning.md)
- [Deployment](docs/octane-docker-deployment.md)
- [Newsletter API](docs/newsletter-api.md)
- [Public content and contact](docs/public-content-and-contact.md)
- [Storefront discounts](docs/storefront-discounts.md)

## Contribution notes

Preserve the dependency direction between Presentation, Domain, and Infrastructure layers. Put interfaces in the domain, technical implementations in infrastructure, and HTTP translation in presentation. Add tests for authorization, storefront scoping, inventory, money, idempotency, and webhook changes. Coordinate shared-schema changes with the Admin Service before implementation.
