# Root-category storefront API

The storefront API exposes catalog, cart, and checkout operations constrained to one active root category and its active descendants. The existing unscoped `/api/v1` endpoints remain available for the full store.

## Base path

```text
/api/v1/storefronts/{storefront}
```

`{storefront}` is the root category slug. It must identify an active category with no parent. Unknown, inactive, and child-category slugs return `404 Storefront not found`.

For a root category with the slug `hair`, the frontend API base is:

```text
/api/v1/storefronts/hair
```

## Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/homepage` | Return the fully hydrated, currently published storefront homepage |
| `GET` | `/categories` | Return only the storefront root and its active descendant tree |
| `GET` | `/products` | Return products inside the storefront category tree |
| `GET` | `/products/{slug}` | Return an in-storefront product or `404` |
| `GET` | `/collections/{collection}/products` | Return collection products intersected with the storefront scope |
| `POST` | `/cart` | Add an in-storefront product |
| `GET` | `/cart` | Return only cart items in this storefront |
| `PATCH` | `/cart/items/{itemId}` | Update one cart line by its returned cart item ID |
| `DELETE` | `/cart/items/{itemId}` | Remove one cart line by its returned cart item ID |
| `PATCH` | `/cart/{productId}` | Legacy product-based update; returns `409` when multiple variants are present |
| `DELETE` | `/cart/{productId}` | Legacy product-based removal; returns `409` when multiple variants are present |
| `DELETE` | `/cart` | Clear only this storefront's cart items |
| `POST` | `/cart/merge` | Merge only this storefront's guest items into the authenticated cart |
| `POST` | `/checkout/cart` | Create an order from only this storefront's cart items |

Catalog query parameters and facets are identical to the existing product catalog API. A child `category` filter is intersected with the storefront root scope; it cannot escape into another category tree.

## Guest carts

Guest cart behavior is unchanged. Save the `cart_token` returned by the first add request and send it on later cart and checkout requests:

```http
X-Cart-Token: <uuid>
```

A cart token or authenticated user may have products from more than one storefront. Each scoped route reads, mutates, merges, clears, and checks out only the products within its own category tree.

Cart items expire seven days after their latest add, update, or merge operation. A successful merge response includes `guest_cart_empty`; clients should only discard the guest token when this value is `true`, because the same token may still contain items belonging to another storefront. The merge is atomic: if any item is unavailable or the combined quantity exceeds stock, no guest or authenticated cart rows are changed.

## Example

```http
GET /api/v1/storefronts/hair/products?brand=example&per_page=24
```

```http
POST /api/v1/storefronts/hair/cart
Content-Type: application/json

{
  "product_id": "01H...",
  "product_variant_id": "01H...",
  "quantity": 1
}
```

Product IDs outside the `hair` category tree are rejected with `404`.

`product_variant_id` is required for variable products. It may be omitted for a simple product, in which case its published default variant is selected automatically. Cart responses include the cart item `id`, `product_variant_id`, `sku`, `attributes`, and variant-specific `unit_price`. Use the cart item `id` for subsequent update and removal requests.

## Variant-cart migration

Run the database migrations before deploying frontend code that sends `product_variant_id`:

```text
php artisan migrate
```

The migration backfills every existing cart row with its product's default variant and then makes `product_variant_id` required. It intentionally stops with an explanatory error if an existing cart product has no variant, so catalog data should be checked before deployment.

## Storefront homepage

```http
GET /api/v1/storefronts/fragrances/homepage
```

The endpoint reads the admin-owned merchandising tables directly from the shared database. It never calls the administrative service. Only active records whose schedule includes the current application-timezone time are returned. A null `starts_at` or `ends_at` is an open boundary; future, expired, and disabled content is excluded. If multiple campaigns are current, the highest `priority` wins, with latest `updated_at` as the tie-breaker. Sections are ordered by `display_order`, then ID.

The campaign is `null` when none is published. `sections` is an empty array when none are published. Draft/internal fields—including campaign `internal_name`, administrator IDs, audit fields, and raw section configuration—are never exposed.

Supported section types are:

- `featured_products`: featured published products, using allowlisted sort/direction options.
- `sale_products`: available products with a currently scheduled positive sale price lower than the original price. Simple products use product pricing; variable products use the default variant.
- `category_products`: products from the configured category and its active descendants, provided that category belongs to the storefront tree.
- `collection_products`: products from an active configured collection, preserving `collection_product.sort_order` and intersecting the storefront tree.
- `newest_products`: newest available published products.
- `random_categories`: active immediate child categories, optionally excluding categories with no published products. Selection is randomized only when the homepage cache entry is generated.
- `manual_products`: published products in exact `storefront_homepage_section_product.display_order`. Missing or inaccessible IDs are not replaced.

Stored configuration is treated as untrusted. Limits are clamped to `STOREFRONT_HOMEPAGE_MAX_ITEMS`; sort fields are limited to `created_at`, `price`, `sort_order`, and `name`; direction is limited to `asc` or `desc`; referenced slugs are validated. A malformed known section remains present with empty `items`, while an unsupported section type is omitted and logged.

Product items use the same shape as the catalog product-list resource. `price` is the original price, `salePrice` is the valid current discounted price or `null`, `currentPrice` is the payable price, and `isOnSale` is true only when the current sale price is positive, scheduled, and lower than the original. Items also include images, brand, category, availability, stock, and variants. Category items use the catalog category shape and add `product_count`.

Example response:

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "storefront": {
      "slug": "fragrances",
      "name": "Fragrances"
    },
    "campaign": {
      "id": "01K1ABCDEF1234567890ABCDEF",
      "eyebrow": "After-dark fragrances",
      "title": "Leave a trace.",
      "description": "A magnetic collection for after dark.",
      "desktop_image": "https://cdn.example.com/campaign-desktop.webp",
      "mobile_image": "https://cdn.example.com/campaign-mobile.webp",
      "cta_label": "Shop the campaign",
      "cta_url": "/collections/midnight-edit",
      "starts_at": "2026-08-01T00:00:00.000000Z",
      "ends_at": null
    },
    "sections": [
      {
        "id": "01K1ABCDEF1234567890ABCDEG",
        "type": "featured_products",
        "title": "Currently coveted",
        "subtitle": null,
        "display_order": 1,
        "items": []
      }
    ]
  }
}
```

### Homepage caching

The fully hydrated response is cached under `storefront:{storefront}:homepage:v2` with the `homepage` and `storefronts` Redis tags. Cache freshness is controlled by `STOREFRONT_HOMEPAGE_CACHE_TTL` (default `300` seconds), and model observers invalidate the response after committed changes to catalog content, media, campaigns, or homepage sections. Set the TTL to `0` to disable homepage caching. `STOREFRONT_HOMEPAGE_MAX_ITEMS` defaults to `50` and provides the runtime safety cap.

Public GET responses also include a short browser/CDN cache policy and an ETag. The defaults are `max-age=60`, `s-maxage=300`, and `stale-while-revalidate=30`; they can be changed through the `PUBLIC_HTTP_CACHE_*` environment settings. Customer-specific and transactional responses are never marked public.
