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

## Combined Queue Worker And Scheduler Image

`Dockerfile.worker` builds a non-HTTP process image that runs the Redis queue
worker and Laravel scheduler together under Supervisor. Both processes write to
container stdout/stderr, restart automatically if they exit, and receive clean
termination signals when the container stops.

Build and run it locally:

```bash
docker build -f Dockerfile.worker -t glamrush-backend-worker .
docker run -d --name glamrush-backend-worker \
  --env-file .env.octane \
  -e RUN_MIGRATIONS=false \
  -e REDIS_QUEUE=glamrush-storefront \
  glamrush-backend-worker
```

For Railway, create a separate service from the backend repository and set its
Dockerfile path to `/Dockerfile.worker`. Do not attach a public domain because
the image does not expose an HTTP server. It must receive the same application,
database, Redis, mail, storage, and provider variables as the API service.

Important deployment rules:

- Keep `RUN_MIGRATIONS=false`; migrations belong in the API pre-deploy step.
- Keep this combined service at one replica. Scaling it also scales the
  scheduler. If more queue throughput is needed, split the scheduler into its
  own service before adding worker replicas.
- `REDIS_QUEUE` defaults to `glamrush-storefront`, keeping it isolated from the
  admin queue.
- Supervisor restarts the queue worker when `QUEUE_WORKER_MAX_TIME` is reached,
  providing periodic process recycling without stopping the scheduler.

Optional worker tuning variables:

```env
QUEUE_CONNECTION=redis
REDIS_QUEUE=glamrush-storefront
QUEUE_WORKER_SLEEP=3
QUEUE_WORKER_TRIES=3
QUEUE_WORKER_BACKOFF=5
QUEUE_WORKER_TIMEOUT=60
QUEUE_WORKER_MAX_TIME=3600
QUEUE_WORKER_MEMORY=256
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
