# Storefront discount codes

The admin service owns discount configuration in `discount_codes`, `discount_code_storefronts`, and `discount_code_targets`. This service owns eligibility checks, order snapshots, usage reservations, and redemptions in the shared database.

## Preview a code

`POST /api/v1/storefronts/{storefront}/discounts/validate`

Send the same `X-Cart-Token` used by a guest cart; authenticated customers use their Sanctum session. `email` is required by a code that has a per-customer limit or is restricted to first orders.

```json
{
  "code": "WELCOME10",
  "email": "shopper@example.com",
  "shipping_amount": 2500
}
```

The response contains the applicable merchandise discount, shipping discount, and preview total. `shipping_amount` only supports the preview and is never trusted at checkout.

## Checkout

Add the validated code to the existing checkout request:

```json
{
  "discount_code": "WELCOME10"
}
```

Checkout reloads and locks the discount row, recalculates product prices and shipping, checks storefront/catalog targets and usage limits, stores order and item discount snapshots, and creates a reservation in the same transaction. The resulting `OrderResource` includes `discount_code`, `discount_amount`, `shipping_discount_amount`, and item-level `line_subtotal` and `discount_amount`.

Reservations become redeemed when an online payment succeeds or a pay-on-delivery order is accepted. Cancelling or expiring a pending-payment order releases its reservation. Keep the Laravel scheduler running so `orders:expire-pending` executes every minute alongside the outbox dispatcher.
