# Cloudflare R2 storage

The Backend Service reads Spatie Media Library records created by the Admin Service and returns their public URLs to storefront clients. It defines the same `r2` disk so media records whose `disk` value is `r2` resolve consistently in both Laravel applications.

## Required environment

```dotenv
FILESYSTEM_DISK=r2
MEDIA_DISK=r2

R2_ACCESS_KEY_ID=your_read_only_access_key
R2_SECRET_ACCESS_KEY=your_read_only_secret
R2_BUCKET=commerce-media-production
R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
R2_URL=https://media.example.com
R2_REGION=auto
R2_USE_PATH_STYLE_ENDPOINT=false
```

Use the same bucket, endpoint, public URL, and object keys as the Admin Service. Give this service a separate R2 token scoped to **Object Read** unless a documented Backend workflow requires writes.

`R2_ENDPOINT` is used for authenticated S3 operations. `R2_URL` is the publicly readable custom domain returned for permanent product/content media URLs. The current resources use permanent URLs, so the bucket custom domain must allow public reads.

After changing secrets or configuration:

```bash
php artisan optimize:clear
php artisan queue:restart
php artisan octane:reload
```

## Verification

With the Admin Service configured for R2:

1. Upload a product image through Admin.
2. Confirm its `media.disk` value is `r2`.
3. Request the Backend product-detail endpoint.
4. Confirm original and conversion URLs use `R2_URL`.
5. Load the product page through the Storefront and inspect the image response.

If the database row says `gcs`, the Backend intentionally uses the legacy GCS disk. Changing `MEDIA_DISK` does not migrate old Spatie records.

## Existing media cutover

The Admin Service owns the migration procedure. Objects must be copied from GCS to identical R2 keys before changing `media.disk` or `media.conversions_disk`. Keep the GCS disk configured until all legacy rows have been migrated and verified.

## Security

- Do not expose either R2 access key to Nuxt runtime configuration.
- Keep credentials in the Backend deployment's secret store.
- Use separate credentials for Admin and Backend.
- Never store customer-private files in the public catalog bucket.
- Browser uploads should use short-lived presigned URLs and restrictive CORS if introduced later.

Cloudflare references: [S3-compatible API](https://developers.cloudflare.com/r2/get-started/s3/) and [public buckets/custom domains](https://developers.cloudflare.com/r2/buckets/public-buckets/).
