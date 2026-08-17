# Backend Service feature catalog

The definitive endpoint list is in `routes/api.php`; this catalog explains how customer-facing capabilities behave and where detailed contracts live.

## Storefront discovery and merchandising

- Storefront context resolved from an active root category slug
- Header announcement and navigation configuration
- Campaign-driven hero content
- Ordered homepage sections with a maximum product count per section
- Category trees scoped to the storefront
- Featured, newest, sale, collection, category, and manual product selections

See [Storefront API](storefront-api.md).

## Product catalog

- Storefront-scoped product listing and detail by slug
- Search across product, brand, and assigned-category data
- Brand, category, price, sale, and product-attribute filters
- Facet counts based on distinct products
- Sorting and pagination without pivot duplicates
- Simple and variable products
- Multiple product/variant images and effective pricing
- Primary category plus the complete categories collection

See [Product catalog filters](product-catalog-filters.md).

## Customer authentication

- Email/password registration and login
- Google social authentication
- HttpOnly Sanctum stateful SPA sessions with CSRF protection
- Current-user hydration and logout
- Password-reset code request, verification, and reset
- Welcome email dispatched asynchronously after registration
- Dedicated brute-force limits for authentication and password recovery

## Guest and customer carts

- Guest cart creation through `X-Cart-Token`
- Authenticated customer carts
- Explicit guest-cart merge after login or registration
- Variant selection for variable products
- Quantity validation against available stock
- Add, update, remove, and clear operations
- Cart restoration from failed or expired orders

Cart mutation endpoints are rate-limited. The final checkout transaction revalidates all cart information.

## Customer account

- Saved-item/wishlist list, add, remove, and synchronization
- Address create, update, delete, and default selection
- Country, state, and city location options
- Paginated order history

## Shipping and checkout

- Address-based shipping-zone resolution
- Eligible shipping-method/rate calculation
- Discount preview using the current cart
- Idempotent pending-order creation
- Immutable order-line price, category, brand, collection, and discount snapshots
- Inventory reservation with later commit or release
- Cart quantity/removal support before submission

## Discounts

- Code availability windows and active state
- Global and per-customer usage limits
- Minimum subtotal/quantity and customer eligibility rules
- Product, variant, brand, collection, storefront, and multi-category targeting
- Category ancestor matching
- Percentage, fixed, and shipping discount calculations supported by configured definitions
- Redemption reservation and completion tied to order/payment state

See [Storefront discounts](storefront-discounts.md).

## Orders and payments

- Pending-payment order creation
- Payment-on-delivery order path where configured
- Paystack and Flutterwave initialization and verification
- Signed provider webhook handling
- Browser payment callback support through the Storefront
- Idempotent state transitions and transaction records
- Successful, failed, and pending-on-delivery customer email
- Admin new-order and payment-failure notifications from configured recipient lists
- Automatic expiry and inventory release for abandoned pending orders

See [Order and payment idempotency](order-payment-idempotency.md).

## Public content and contact

- Published static pages scoped to a storefront
- Categorized, searchable FAQs
- Validated contact submissions with honeypot support
- Submission reference returned without exposing internal records
- Notification email and submission throttling

See [Public content and contact](public-content-and-contact.md).

## Newsletter

- Subscribe and pending-confirmation states
- Email confirmation token
- Confirmation resend
- Unsubscribe
- Email- and IP-based throttling

Admin listing and CSV export are implemented in the Admin Service. See [Newsletter API](newsletter-api.md).

## Configuration and caching

- Admin-managed runtime settings with environment fallback
- Cached storefront context, homepage, payment methods, and shipping configuration
- Query cache with exception-safe callbacks
- Tag/version-based invalidation after catalog/configuration commits
- Short browser/CDN cache headers on appropriate public GET responses
- Redis-backed counters for API rate limiting and cache metrics

See [Shared cache versioning](shared-cache-versioning.md).

## Events and email

- Domain events for user registration, order placement, order payment states, and payment failure
- Laravel, RabbitMQ, and Kafka event transports
- Transactional outbox delivery
- Welcome, newsletter confirmation, order, payment, contact, and administrator notification mail

See [Event bus](event-bus.md).
