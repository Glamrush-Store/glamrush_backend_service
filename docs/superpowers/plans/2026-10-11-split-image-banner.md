# Split Image Banner Implementation Plan

**Goal:** Add the `split_image_banner` homepage section to the public storefront API and storefront UI while preserving existing homepage behavior, media conventions, caching, and configured ordering.

**Architecture:** The admin-owned homepage-section record remains the source of configuration and Spatie media. The backend hydrates a defensive public DTO through `StorefrontHomepageService`; the frontend consumes that DTO through the existing homepage section renderer and a dedicated responsive component. No backend migration is added.

**Tech stack:** Laravel, Eloquent, Spatie Media Library, Pest, Vue/Nuxt, TypeScript, Tailwind/CSS.

---

## Task 1: Complete the backend section and media contract

**Files:**
- Modify: `app/Domain/Storefront/Enums/HomepageSectionType.php`
- Modify: `app/Infrastructure/Persistence/Eloquent/Models/StorefrontHomepageSection.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/Storefront/StorefrontHomepageTest.php`

1. Add focused assertions that the enum recognizes `split_image_banner` and that the section model exposes the left, right, and mobile single-file media collections.
2. Retain the existing enum case, `HasMedia` implementation, `InteractsWithMedia` trait, supported MIME types, and morph-map alias where they match the contract.
3. Run the focused model/homepage tests and fix only contract-specific failures.

## Task 2: Harden public hydration and section ordering

**Files:**
- Modify: `app/Domain/Storefront/Services/StorefrontHomepageService.php`
- Modify: `app/Infrastructure/Persistence/Eloquent/Models/StorefrontHomepageSection.php`
- Test: `tests/Feature/Storefront/StorefrontHomepageTest.php`

1. Add tests for media-to-slot mapping, mobile media, ascending item order with slot tie-breaking, complete and incomplete CTAs, malformed entries, and top-level `display_order`/`id` ordering.
2. Ensure homepage queries eager-load media and use the existing ordered scope without section-type prioritization.
3. Normalize and filter split-banner configuration defensively, use `SafeMediaUrl`, and return `cta: null` unless both values are present.
4. Run the complete storefront homepage test file and relevant regressions.

## Task 3: Complete cache invalidation coverage

**Files:**
- Modify: `app/Infrastructure/Caching/CacheInvalidationObserver.php`
- Modify: `tests/Unit/Caching/CacheInvalidationObserverTest.php`
- Modify: `tests/Feature/Caching/ConfigurationCacheTest.php`

1. Add unit coverage for the `storefront_homepage_section` media morph alias.
2. Add or extend an integration test proving a section media mutation invalidates the homepage cache family.
3. Keep existing model invalidation behavior unchanged and run all cache invalidation tests.

## Task 4: Add the responsive storefront renderer

**Files:**
- Modify: `C:/Users/USER/PhpstormProjects/glamrush_storefront/app/types/catalog.ts`
- Modify: `C:/Users/USER/PhpstormProjects/glamrush_storefront/app/components/home/MerchandisingSection.vue`
- Create: `C:/Users/USER/PhpstormProjects/glamrush_storefront/app/components/home/SplitImageBanner.vue`
- Modify: `C:/Users/USER/PhpstormProjects/glamrush_storefront/app/pages/index.vue`

1. Add typed split-banner items, CTA data, and mobile image support.
2. Build a dedicated component with two diagonally meeting desktop panels and one shared mobile image, rendering optional CTAs safely.
3. Route the new type through the existing section component.
4. Render all dynamic sections from one array sorted by `display_order`, then `id`, so no type is moved ahead of configured order.
5. Run the frontend production build and repair any type, template, or styling errors.

## Final verification

1. Run Pint on changed backend PHP files.
2. Run the complete storefront homepage tests, cache invalidation tests, and relevant regression tests.
3. Run the frontend production build.
4. Review diffs to confirm no migration or unrelated refactor was introduced.
