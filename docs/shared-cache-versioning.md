# Shared cache versioning

The admin and storefront services share PostgreSQL but use isolated Redis prefixes and queue names. Each application owns its Redis keys. Cross-service invalidation is coordinated through the shared `cache_versions` database table rather than by allowing the admin service to delete storefront Redis keys.

## Namespaces

| Namespace | Change types |
|---|---|
| `catalog` | Products, variants, inventory, attributes, categories, brands, collections, catalog media and storefront category resolution |
| `homepage` | Campaigns, homepage sections, ordered section products and campaign/catalog media used by the homepage |
| `payment-methods` | Public payment-method configuration |
| `shipping` | Shipping methods, zones and rates |

Storefront cache keys include the current versions for the tags involved. The storefront caches a version snapshot inside its own Redis prefix for `CACHE_VERSION_CHECK_TTL_SECONDS`, which defaults to 10 seconds. Consequently, an admin-originated change bypasses old storefront Redis values no later than that interval. Old versioned entries expire under their normal cache TTL.

Local storefront writes retain the existing after-commit tagged-cache flush and also increment the shared version. Local changes are therefore visible immediately and inform other applications using the same contract.

## Admin write contract

The backend service owns the `cache_versions` migration. Do not duplicate it in the admin repository. The admin service must increment the affected namespaces in the same database transaction as its write.

For each affected namespace, use this concurrency-safe sequence:

```php
$now = now();

DB::table('cache_versions')->insertOrIgnore([
    'namespace' => $namespace,
    'version' => 1,
    'created_at' => $now,
    'updated_at' => $now,
]);

DB::table('cache_versions')
    ->where('namespace', $namespace)
    ->increment('version', 1, ['updated_at' => $now]);
```

Place this code inside the transaction that changes catalog/configuration data. Updating the version before commit is safe because other database connections cannot observe it until the transaction commits. If the write rolls back, its version increment rolls back as well.

Admin invalidation mapping:

- Product, variant, inventory or product attribute: `catalog`, `homepage`
- Category: `catalog`, `homepage`
- Brand or collection: `catalog`, `homepage`
- Catalog product/category media: `catalog`, `homepage`
- Campaign or homepage section: `homepage`
- Campaign media: `homepage`
- Payment method: `payment-methods`
- Shipping method, zone or rate: `shipping`

The admin application should centralize this mapping in one after-write service or observer and test that increments occur only after successful domain writes. It must never call `Cache::flush()` against the storefront prefix.

## HTTP/Cloudflare caching

Public API responses use a 60-second browser and shared-cache lifetime with 15 seconds of stale-while-revalidate by default. Redis versioning cannot purge a response already stored at the Cloudflare edge, so these deliberately short HTTP bounds remain necessary unless Cloudflare cache-tag purging is introduced later.

Never cache authentication, cart, checkout, account, payment mutation, webhook, CSRF or session responses at the edge.
