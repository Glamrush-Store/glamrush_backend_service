# Newsletter subscription API

The customer-facing backend owns the `newsletter_subscribers` table and the public subscription lifecycle. It does not expose subscriber listings or exports. Administrative listing and export belong in the admin application.

## Lifecycle

1. `POST /api/v1/newsletter/subscriptions` stores normalized consent as `pending` and queues a confirmation email.
2. The email links to `GET /api/v1/newsletter/subscriptions/confirm/{token}`. A successful confirmation changes the status to `subscribed`.
3. `POST /api/v1/newsletter/subscriptions/resend-confirmation` rotates an expired or lost confirmation token for a pending subscriber.
4. `POST /api/v1/newsletter/subscriptions/unsubscribe` changes the status to `unsubscribed` when given the subscriber's private unsubscribe token.
5. Submitting the subscription form again after unsubscribing creates a new pending consent cycle.

Subscribe and resend return generic responses so callers cannot discover whether an email address is already registered. Confirmation and unsubscribe tokens are cryptographically random and only their SHA-256 hashes are stored.

## Subscribe

```http
POST /api/v1/newsletter/subscriptions
Content-Type: application/json

{
  "email": "customer@example.com",
  "source": "storefront-footer"
}
```

Successful requests return HTTP `202`. The optional `source` is limited to 100 characters.

## Confirm

```http
GET /api/v1/newsletter/subscriptions/confirm/{64-character-token}
```

The default confirmation lifetime is 24 hours. Invalid and expired tokens return HTTP `422`.

## Resend confirmation

```http
POST /api/v1/newsletter/subscriptions/resend-confirmation
Content-Type: application/json

{
  "email": "customer@example.com"
}
```

## Unsubscribe

```http
POST /api/v1/newsletter/subscriptions/unsubscribe
Content-Type: application/json

{
  "token": "64-character-private-unsubscribe-token"
}
```

The unsubscribe token is intended to be embedded in links generated for outgoing marketing messages. A future marketing provider integration can use provider-managed unsubscribe links and update this table from signed webhooks.

## Configuration

```dotenv
NEWSLETTER_CONFIRMATION_TTL_MINUTES=1440
API_RATE_LIMIT_NEWSLETTER_EMAIL_PER_HOUR=3
API_RATE_LIMIT_NEWSLETTER_IP_PER_HOUR=10
API_RATE_LIMIT_NEWSLETTER_ACTION_PER_MINUTE=10
```

Confirmation delivery uses the application's configured Laravel mailer and queue. A queue worker must be running for queued confirmation messages to be delivered.
The storefront queue is named by `REDIS_QUEUE=glamrush-storefront`; workers should consume that queue rather than a shared default queue.
