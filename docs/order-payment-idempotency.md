# Order and payment idempotency

## Client contract

`POST /api/v1/checkout/cart`, storefront-scoped checkout, and `POST /api/v1/payments/initialize` require an `Idempotency-Key` header.

- Use a random key containing 16–100 letters, numbers, dots, underscores, colons, or hyphens.
- Reuse the same key only when retrying the same logical request with the same JSON payload.
- Use a new key when starting another checkout or selecting a different payment method.
- A replay returns the original resource with `Idempotent-Replayed: true`.
- Reusing a key with a different payload returns `422`.

Authenticated requests scope keys to the customer account. Guest requests scope keys to `X-Cart-Token`. Payment initialization also verifies that the account or cart token owns the order.

## Checkout guarantees

Checkout is serialized with a Redis lock and persisted with a unique `(idempotency_owner, idempotency_key)` database constraint. Order creation, stock reservation, cart consumption, and the `order.placed` outbox message occur in one transaction. A retry returns the existing order without reserving inventory again.

## Payment initialization guarantees

Payment initialization persists its key, request fingerprint, payment reference, and gateway response. A retry returns the same payment and authorization URL instead of creating another payment or gateway session. If the process stopped after creating the payment but before saving the provider response, the same payment reference is reused to resume initialization.

## Verification and webhooks

Gateway verification happens before opening the database transaction. Finalization then:

1. locks the payment row;
2. deduplicates the provider transaction event;
3. validates provider, amount, and currency;
4. marks the payment and order paid;
5. converts reserved stock into committed stock once;
6. records a unique `order.paid` outbox message;
7. commits everything atomically.

Repeated verification and valid webhook deliveries return successfully without repeating inventory or notification side effects. A provider transaction cannot be assigned to two payments.

## Transactional outbox

Order and payment notifications are recorded in `outbox_messages` inside the business transaction. `DeliverOutboxMessage` is dispatched after commit. The scheduled `outbox:dispatch` command retries unprocessed messages every minute, covering queue-dispatch failures after a database commit.

Production must run both the queue worker and Laravel scheduler:

```shell
php artisan queue:work
php artisan schedule:work
```
