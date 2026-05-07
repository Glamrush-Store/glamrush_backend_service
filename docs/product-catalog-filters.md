# Product Catalog Filter API

**Endpoint:** `GET /api/v1/products`
**Auth:** Not required

---

## Query Parameters

| Parameter   | Type    | Default | Description                                                |
|-------------|---------|---------|------------------------------------------------------------|
| `category`  | string  | —       | Filter by category slug (e.g. `shoes`)                     |
| `brand`     | string  | —       | Filter by brand slug (e.g. `nike`)                         |
| `search`    | string  | —       | Full-text search against product name and slug             |
| `price_min` | number  | —       | Minimum price (inclusive, matched against default variant) |
| `price_max` | number  | —       | Maximum price (inclusive, matched against default variant) |
| `featured`  | boolean | —       | `true` returns only featured products                      |
| `sort`      | string  | —       | Column to sort by (e.g. `name`, `price`)                   |
| `direction` | string  | `asc`   | Sort direction: `asc` or `desc`                            |
| `page`      | integer | `1`     | Page number                                                |
| `per_page`  | integer | `20`    | Items per page                                             |
| `filters`   | JSON    | `{}`    | Advanced Purity filter object (see below)                  |

All parameters are optional and combinable. Omitting a parameter does not filter on that dimension.

---

## Response Shape

```json
{
    "success": true,
    "message": "Success",
    "data": [
        ...products
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 98,
        "from": 1,
        "to": 20
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "facets": {
        "price_range": {
            "min": 12.99,
            "max": 450.00
        },
        "brands": [
            ...brand
            objects
        ],
        "categories": [
            ...category
            objects
        ],
        "attributes": [
            ...attribute
            group
            objects
        ]
    }
}
```

---

## Product Object

```json
{
    "id": "01jk...",
    "name": "Air Max 90",
    "slug": "air-max-90",
    "sku": "AM90-001",
    "type": "simple | variable",
    "price": 129.99,
    "isOnSale": false,
    "available": true,
    "is_featured": false,
    "sort_order": 0,
    "images": [
        {
            "id": 1,
            "name": "main",
            "url": "...",
            "thumb": "...",
            "medium": "..."
        }
    ],
    "category": {
        "id": "...",
        "name": "Shoes",
        "slug": "shoes"
    },
    "brand": {
        "id": "...",
        "name": "Nike",
        "slug": "nike"
    },
    "default_attributes": [
        {
            "type": "color",
            "code": "#000000",
            "value": "Black",
            "display_type": "color",
            "meta": null
        }
    ],
    "variants": [
        {
            "id": "01jk...",
            "sku": "AM90-001-BLK-42",
            "images": [],
            "isDefault": true,
            "price": 129.99,
            "salePrice": null,
            "inStock": true,
            "isOnSale": false,
            "available": true,
            "attributes": [
                {
                    "type": "color",
                    "code": "#000000",
                    "value": "Black",
                    "display_type": "color",
                    "meta": null
                },
                {
                    "type": "size",
                    "code": null,
                    "value": "42",
                    "display_type": "text",
                    "meta": null
                }
            ]
        }
    ]
}
```

`price` on the product is always the effective price (sale price when active, regular price otherwise). For
`type: "simple"`, `variants` will have a single default entry. For `type: "variable"`, iterate `variants` for per-option
pricing and stock.

---

## Facets

Facets describe the available filter options for the **current query context**. They are designed for building a dynamic
filter sidebar.

### `price_range`

```json
"price_range": {"min": 12.99, "max": 450.00}
```

Min/max prices from all products matching active filters, **excluding** any active `price_min`/`price_max` filters. Use
these as the bounds of a price range slider.

### `brands`

```json
"brands": [
{"id": "01jk...", "name": "Nike", "slug": "nike", "count": 24
},
{
"id": "01jk...", "name": "Adidas", "slug": "adidas", "count": 11}
]
```

Brands available in the current result set, sorted by count descending. Computed with **AND-except-self** logic: all
active filters are applied *except* the brand filter, so a user filtering by Nike still sees all other brands with their
counts. Use `slug` as the value for the `brand` query parameter.

### `categories`

```json
"categories": [
{"id": "01jk...", "name": "Shoes", "slug": "shoes", "count": 30
},
{
"id": "01jk...", "name": "Sandals", "slug": "sandals", "count": 8}
]
```

Same AND-except-self logic as brands. Use `slug` as the value for the `category` query parameter.

### `attributes`

```json
"attributes": [
{
"type": "color",
"options": [
{"value": "BLACK", "label": "Black", "code": "#000000", "display_type": "color", "meta": null, "count": 18
},
{
"value": "WHITE", "label": "White", "code": "#FFFFFF", "display_type": "color", "meta": null, "count": 12 }
]
},
{
"type": "size",
"options": [
{"value": "S", "label": "S", "code": null, "display_type": "text", "meta": null, "count": 22
},
{
"value": "M", "label": "M", "code": null, "display_type": "text", "meta": null, "count": 20 },
{"value": "XL", "label": "XL", "code": null, "display_type": "text", "meta": null, "count": 9}
]
}
]
```

Attribute types and their available values from all variants in the filtered product set. Options are sorted by count
descending. Each product is counted once per type+value regardless of how many variants carry that attribute.

| Field          | Description                                                          |
|----------------|----------------------------------------------------------------------|
| `type`         | Attribute key (e.g. `color`, `size`, `material`)                     |
| `value`        | Raw code used for filtering (e.g. `BLACK`)                           |
| `label`        | Human-readable display label (e.g. `Black`)                          |
| `code`         | Styling code — hex colour for `color`, `null` for others             |
| `display_type` | Rendering hint: `color` (swatch), `text` (chip), `image` (thumbnail) |
| `meta`         | Additional data (JSON object or `null`)                              |
| `count`        | Number of products carrying this attribute value                     |

To filter by an attribute, pass the `value` (e.g. `BLACK`) inside the `filters` JSON body (see Filtering by Attributes
below).

---

## Filtering by Attributes

Attribute filtering uses the `filters` query parameter with a JSON object following
the [Purity filter format](https://github.com/abbasudo/laravel-purity).

> **Note:** Pass `filters` as a JSON string in the query string, or as a JSON body when using POST.

### Filter by a single attribute value

```
GET /api/v1/products?filters={"attributes":{"$has":{"type":"color","value":"BLACK"}}}
```

### Filter by multiple values within the same attribute (OR)

```
GET /api/v1/products?filters={"attributes":{"$hasAny":[{"type":"color","value":"BLACK"},{"type":"color","value":"WHITE"}]}}
```

### Filter by attributes across multiple types (AND — e.g. Black AND Size M)

```
GET /api/v1/products?filters={"$and":[{"attributes":{"$has":{"type":"color","value":"BLACK"}}},{"attributes":{"$has":{"type":"size","value":"M"}}}]}
```

---

## Common Usage Patterns

### Initial page load (no filters)

```
GET /api/v1/products?per_page=24
```

Renders the full catalog with all facet options populated.

### Category landing page

```
GET /api/v1/products?category=shoes&per_page=24
```

`facets.brands` shows all brands that have shoes. `facets.price_range` reflects shoe prices only.

### User applies a brand filter

```
GET /api/v1/products?category=shoes&brand=nike
```

`facets.brands` still shows all brands with counts (AND-except-self), so the user can switch from Nike to Adidas without
losing context. `facets.categories` narrows to categories that have Nike products.

### User uses price slider

```
GET /api/v1/products?category=shoes&price_min=50&price_max=150
```

`facets.price_range` returns the full range for that category (ignoring the active price filter), so the slider always
shows the correct bounds.

### Search with facets

```
GET /api/v1/products?search=running+shoes&brand=nike
```

All facets are scoped to the search results.

### Pagination

```
GET /api/v1/products?category=shoes&brand=nike&page=2&per_page=24
```

Facets do **not** change with page — they always reflect the full filtered set, not just the current page.

---

## State Management Tips

- Store active filters as a flat object `{ category, brand, price_min, price_max, search, attributes: [] }`.
- When rendering a facet option, mark it as **active** if its value matches the current filter state.
- When a user selects a brand, update `brand` in the filter state and refetch. The response will contain refreshed
  facets.
- For attribute filters, maintain a list of `{ type, value }` pairs and encode them into the `filters` JSON.
- Persist filter state in the URL query string so pages are shareable and bookmarkable.
- Facets are cached server-side for 5 minutes. Filter UI can be re-fetched on filter change without performance
  concerns.
