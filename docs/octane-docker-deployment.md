# Laravel Octane Docker Deployment

This API service is configured to run Laravel Octane with the Swoole server inside a single Docker image.

## First Setup

Create an environment file for Docker:

```bash
cp .env.octane.example .env.octane
```

Set a production `APP_KEY`:

```bash
php artisan key:generate --show
```

Put the generated value into `.env.octane`.

## Build And Run

```bash
docker build -t glamrush-backend-octane .
docker run -d --name glamrush-backend \
  --env-file .env.octane \
  -p 8000:8000 \
  glamrush-backend-octane
```

The API will listen on:

```text
http://localhost:8000
```

## External Services

This image only runs the Laravel Octane API process. Provide Postgres and Redis outside the container and point the app to them with:

```env
DB_HOST=your-postgres-host
REDIS_HOST=your-redis-host
```

Run queue workers and scheduler as separate containers using the same image.

Queue worker:

```bash
docker run -d --name glamrush-queue \
  --env-file .env.octane \
  glamrush-backend-octane \
  php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Scheduler:

```bash
docker run -d --name glamrush-scheduler \
  --env-file .env.octane \
  glamrush-backend-octane \
  sh -lc "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"
```

## Migrations

The `app` container runs migrations on startup when:

```env
RUN_MIGRATIONS=true
```

Set it to `false` if your deployment platform runs migrations separately.

## Useful Commands

```bash
docker logs -f glamrush-backend
docker exec glamrush-backend php artisan migrate --force
docker exec glamrush-backend php artisan octane:reload
docker stop glamrush-backend
docker rm glamrush-backend
```

## Reverse Proxy

In production, place Nginx, Caddy, Cloudflare, or a load balancer in front of port `8000` and terminate HTTPS there. Set:

```env
APP_URL=https://api.your-domain.com
OCTANE_HTTPS=true
```
