# Public content, FAQs, and contact submissions

The admin service owns and migrates `content_pages`, `content_page_storefronts`, `faq_categories`, `faqs`, and `faq_storefronts`. This backend reads those shared PostgreSQL tables directly and never mutates their configuration. Deploy the admin migration `2026_08_08_100001_create_content_management_tables.php` before deploying these readers. No service-to-service HTTP request is used.

All routes resolve `{storefront}` as an active, non-deleted root category slug. Unknown, child, inactive, and deleted storefronts return the standard 404 envelope.

## Published page

`GET /api/v1/storefronts/{storefront}/pages/{slug}` returns one page only when it is published, has started, has not expired, is not deleted, and is global or assigned to the storefront. Unavailable records all return the same 404. Responses exclude administrators, lifecycle flags, pivots, and deletion fields. Stored HTML is returned only as a JSON string and is never evaluated.

Only contact pages expose `settings`. The response allowlists email, phone, WhatsApp, business hours, address, HTTPS map URL, and recognized HTTPS social links. All other JSON keys are discarded. Page images use the shared `content_page` media morph alias and `content-images` collection.

## FAQs

`GET /api/v1/storefronts/{storefront}/faqs` accepts `category`, `search`, `page`, and `per_page` (maximum 50). It returns eligible FAQ rows grouped by active category. Ordering is category display order, FAQ display order, creation time, then ULID. Empty, inactive, or deleted categories are omitted. Content responses are intentionally not cached so admin publication changes become visible immediately.

## Contact submissions

`POST /api/v1/storefronts/{storefront}/contact-submissions` accepts plain-text `name`, `email`, optional `phone` and `subject`, `message`, optional `source`, and the `website` honeypot. HTML, control-character abuse, and unsafe URL schemes are rejected. Clients cannot set status, customer identity, resolution fields, or metadata.

Successful and honeypot requests return HTTP 202 with only a reference. Legitimate submissions are stored in the backend-owned `contact_submissions` table, associated with the authenticated Sanctum customer when available, deduplicated atomically within a minute, and limited to five requests per minute per IP/storefront. Full personal details are never returned or deliberately logged.

When `CONTACT_NOTIFICATION_ADDRESS` (or `MAIL_ADMIN_ADDRESS`) is configured, a queued internal email is dispatched after commit. Queueing failures are reported without rolling back or hiding the persisted submission. Keep a queue worker running to deliver notifications.
