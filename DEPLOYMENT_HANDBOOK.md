# Kokkonis Catalogue Deployment Handbook

## Purpose

This handbook is the operational guide for developing, testing, releasing, deploying, backing up, restoring, and maintaining the Kokkonis catalogue platform.

It documents the intended production architecture and procedures. It does not indicate that the Docker images, CI workflows, deployment scripts, server, DNS, or production infrastructure have already been implemented.

The platform currently remains a view-only catalogue. It has no prices, cart, checkout, payments, or order processing.

## System ownership

The platform contains two independent applications:

| Application | Repository | Responsibility |
|---|---|---|
| Laravel backend | `kokkonios-shop` | Database, catalogue rules, Filament administration, imports, media metadata, queues, and `/api/v1` |
| Next.js storefront | `kokkonis-storefront` | Public rendering, catalogue browsing, filters, SEO, navigation, and image presentation |

Laravel is the source of truth. Next.js consumes the Laravel API and must not duplicate catalogue data.

## Target architecture

```text
Visitors and administrators
          |
          v
Cloudflare DNS and optional proxy
          |
          v
Hetzner Cloud server
  |
  +-- Reverse proxy and TLS
  |     +-- www.example.gr ------> Next.js storefront
  |     +-- backend.example.gr --> Laravel and Filament
  |
  +-- Private Docker network
        +-- Next.js server
        +-- Laravel web runtime
        +-- Laravel queue worker
        +-- Laravel scheduler
        +-- PostgreSQL 17

Cloudflare R2
  +-- Private production media bucket with public custom media domain
  +-- Private encrypted database-backup bucket

GitHub Actions
  +-- Laravel CI and image publishing
  +-- Next.js CI and image publishing
  +-- Controlled deployment of exact image SHA tags
```

The first production release uses one Hetzner server and Docker Compose. Kubernetes is deliberately excluded.

## Environment model

### Development

Development means a developer workstation with:

- `APP_ENV=local`;
- `APP_DEBUG=true`;
- SQLite for normal local work;
- local media or the dedicated development R2 bucket;
- local test data;
- Vite and Next.js development servers;
- no production credentials;
- no production customer or catalogue data.

SQLite is retained because it is fast, portable, and easy to reset. It is not proof that PostgreSQL-specific behavior works.

### Continuous integration

CI means disposable GitHub-hosted runners with:

- no production application secrets;
- deterministic dependency installation;
- code formatting checks;
- static analysis;
- tests against in-memory SQLite;
- an additional PostgreSQL compatibility job;
- production asset builds;
- Docker image builds after all required checks pass.

### Staging

Staging is optional but recommended before the first public release. It should use:

- a separate hostname;
- a separate PostgreSQL database;
- a separate R2 bucket or prefix;
- separate credentials;
- production-style Docker images;
- production-style debug, queue, cache, session, and proxy settings.

Staging must never connect to the production database.

### Production

Production means:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- PostgreSQL 17;
- permanent media in a production R2 bucket;
- HTTPS-only traffic;
- persistent queue and session storage;
- a continuously supervised queue worker;
- one continuously supervised scheduler;
- encrypted off-server database backups;
- immutable Docker images;
- deployment only after CI succeeds.

## Branch and release model

The recommended branch flow is:

```text
feature branch
    |
    v
pull request into develop
    |
    v
CI and integration review
    |
    v
pull request from develop into main
    |
    v
CI, image build, production approval, deployment
```

Rules:

- Pull requests run CI but never deploy.
- `develop` represents integrated work that is not yet production.
- `main` represents deployable production code.
- Direct pushes to `main` should be prohibited with branch protection.
- Required CI checks must pass before merging into `main`.
- Backend API changes remain additive until the storefront has adopted them.

## Local Laravel development with SQLite

Recommended local environment values:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public
MEDIA_DISK=public
MEDIA_CONVERSIONS_DISK=

LOG_CHANNEL=stack
LOG_LEVEL=debug
```

Initial local setup:

```bash
touch database/database.sqlite
composer install
npm install
php artisan key:generate
php artisan migrate
npm run build
```

Normal development:

```bash
composer dev
```

Local verification:

```bash
composer ci:check
```

The SQLite file must not be copied to production or committed to Git.

## Local Next.js development

Recommended local storefront values:

```dotenv
LARAVEL_API_URL=http://localhost:8000
MEDIA_BASE_URL=http://localhost:8000
```

Local commands:

```bash
npm install
npm run dev
```

The storefront should request real development catalogue data from Laravel. It should not maintain a second product database.

## Development R2 policy

The development R2 bucket is disposable and must remain separate from production.

Development rules:

- Use a development-only access key.
- Restrict that key to the development bucket.
- Permit browser upload origins only for local Laravel development hosts.
- Do not use production media URLs in `.env.example` unless they are safe public examples.
- Do not rely on development objects being retained permanently.

## Production database policy

Production uses PostgreSQL 17. SQLite is not a production option.

PostgreSQL must:

- run on the private Docker network;
- have no host port published to the internet;
- use a dedicated application role;
- use a strong generated password;
- use a persistent Docker volume;
- have a container health check;
- be included in monitoring and backup procedures;
- use a pinned supported image version rather than `latest`.

The initial production database is empty. Laravel creates its schema through migrations.

## Migration policy

### Before production

Every migration must be tested through the complete migration chain:

```bash
php artisan migrate:fresh --env=testing
php artisan migrate:rollback --step=1 --env=testing
composer ci:check
```

CI must also execute migrations against PostgreSQL.

### First production migration

After PostgreSQL is healthy:

```bash
docker compose run --rm backend-php php artisan migrate --force
```

### Subsequent deployments

The migration sequence is:

```text
Create and verify a pre-deployment backup
    |
    v
Run migrations with the new backend image
    |
    v
Recreate backend web, worker, and scheduler containers
    |
    v
Verify health endpoints and logs
```

### Prohibited production commands

Never run these against production:

```bash
php artisan migrate:fresh
php artisan db:wipe
php artisan migrate:reset
php artisan migrate:refresh
```

Do not automatically call `migrate:rollback` when deployment fails. Prefer a forward corrective migration.

### Backward-compatible changes

Breaking schema changes require an expand-and-contract sequence:

1. Add the new field or table without removing the old structure.
2. Deploy backend code that supports both structures.
3. Backfill production data when required.
4. Deploy storefront code that consumes the new contract.
5. Confirm no deployed code uses the old structure.
6. Remove the old structure in a later release.

## Seeder policy

The current default `DatabaseSeeder` is not production-safe because it creates a test account with a known password and invokes example catalogue filter assignment.

Never run the current command in production:

```bash
php artisan db:seed
```

Before production, separate seeders by purpose:

| Seeder | Allowed content | Environment |
|---|---|---|
| Development seeder | Test users, sample products, demonstration attributes | Local and CI only |
| Production seeder | Intentional idempotent baseline configuration | Production when explicitly invoked |
| Catalogue import | Real products and relationships | Filament import workflow |

The first administrator is created interactively:

```bash
docker compose run --rm backend-php php artisan make:filament-user
```

Never commit a default production administrator password.

## Production media policy

Create two separate private R2 buckets:

```text
kokkonis-production-media
kokkonis-production-backups
```

The media bucket holds:

- originals;
- Spatie conversions;
- brand logos;
- menu previews;
- hero images;
- product images.

The backup bucket holds only encrypted database backups.

Use separate least-privilege credentials for each bucket.

Recommended Laravel media values:

```dotenv
FILESYSTEM_DISK=s3
MEDIA_DISK=s3
MEDIA_CONVERSIONS_DISK=s3
MEDIA_QUEUE=media
QUEUE_CONVERSIONS_BY_DEFAULT=true
QUEUE_CONVERSIONS_AFTER_DB_COMMIT=true

AWS_ACCESS_KEY_ID=<production-media-access-key>
AWS_SECRET_ACCESS_KEY=<production-media-secret>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=kokkonis-production-media
AWS_ENDPOINT=https://<cloudflare-account-id>.r2.cloudflarestorage.com
AWS_URL=https://media.example.gr
AWS_USE_PATH_STYLE_ENDPOINT=false
```

R2 rules:

- The media custom domain may be public.
- The bucket itself must not permit listing.
- Upload CORS should allow only the production Laravel admin origin.
- The backup bucket must have no public domain.
- Apply backup retention and deletion-protection rules.
- Do not reuse development credentials.

Cloudflare documents object lifecycle rules at <https://developers.cloudflare.com/r2/buckets/object-lifecycles/>.

## Production Laravel environment

The server-owned backend environment file should resemble:

```dotenv
APP_NAME=Kokkonis
APP_ENV=production
APP_KEY=<generated-once-and-stored-securely>
APP_DEBUG=false
APP_URL=https://backend.example.gr

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=UTC

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=kokkonis
DB_USERNAME=kokkonis_app
DB_PASSWORD=<strong-postgres-password>

CACHE_STORE=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=database

FILESYSTEM_DISK=s3
MEDIA_DISK=s3
MEDIA_CONVERSIONS_DISK=s3
MEDIA_QUEUE=media
QUEUE_CONVERSIONS_BY_DEFAULT=true
QUEUE_CONVERSIONS_AFTER_DB_COMMIT=true

AWS_ACCESS_KEY_ID=<production-media-access-key>
AWS_SECRET_ACCESS_KEY=<production-media-secret>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=kokkonis-production-media
AWS_ENDPOINT=https://<cloudflare-account-id>.r2.cloudflarestorage.com
AWS_URL=https://media.example.gr
AWS_USE_PATH_STYLE_ENDPOINT=false

MAIL_MAILER=<production-mail-driver>
MAIL_HOST=<production-mail-host>
MAIL_PORT=<production-mail-port>
MAIL_USERNAME=<production-mail-user>
MAIL_PASSWORD=<production-mail-password>
MAIL_FROM_ADDRESS=<production-sender-address>
MAIL_FROM_NAME=Kokkonis
```

Generate `APP_KEY` once:

```bash
php artisan key:generate --show
```

Do not run `key:generate` during deployments. Changing `APP_KEY` invalidates encrypted application data and active sessions.

## Production Next.js environment

The storefront runtime environment should resemble:

```dotenv
NODE_ENV=production
LARAVEL_API_URL=http://backend-web
MEDIA_BASE_URL=https://media.example.gr
NEXT_TELEMETRY_DISABLED=1
PORT=3000
HOSTNAME=0.0.0.0
```

`LARAVEL_API_URL` remains server-only and must not use a `NEXT_PUBLIC_` prefix.

The current `next.config.ts` evaluates the Laravel and media URLs during the build. The Docker build must therefore receive safe production URL values. The deployment implementation must verify that no page requires a live production API response merely to finish `next build`.

The production Next.js configuration should use standalone output:

```ts
output: "standalone"
```

Next.js documents standalone Docker deployment at <https://nextjs.org/docs/app/getting-started/deploying>.

## Docker service topology

The production Compose project should contain:

| Service | Purpose | Persistent state |
|---|---|---|
| `proxy` | HTTPS termination and host routing | Certificates and proxy state |
| `storefront` | Next.js server | Optional single-instance Next cache |
| `backend-web` | Web server in front of PHP-FPM | None |
| `backend-php` | Laravel HTTP runtime | None |
| `backend-worker` | Media and default queue processing | None |
| `backend-scheduler` | Laravel scheduled command runner | None |
| `postgres` | Production database | PostgreSQL volume |

Only the proxy publishes ports 80 and 443.

PostgreSQL, PHP-FPM, the queue worker, the scheduler, and Next.js communicate over private Docker networks.

Production Compose rules:

- Do not bind-mount application source code.
- Use immutable application images.
- Use `restart: unless-stopped` for long-running services.
- Configure health checks.
- Rotate container logs.
- Pin database and proxy versions.
- Do not expose PostgreSQL port 5432.
- Do not place secrets directly in `compose.yaml`.
- Store secrets in server-owned environment files with mode `600`.

Docker's production Compose guidance is available at <https://docs.docker.com/compose/how-tos/production/>.

## Laravel container responsibilities

The Laravel production image needs:

- PHP 8.5;
- PHP-FPM;
- `pdo_pgsql`;
- `pcntl`;
- ZIP support;
- Imagick;
- WebP and AVIF capabilities;
- the image optimizer binaries used by Spatie;
- Composer production dependencies;
- compiled Vite and Filament assets;
- writable `storage` and `bootstrap/cache` directories;
- a non-root runtime user.

The image must not contain `.env` or production credentials.

After runtime environment variables are available, each relevant Laravel container should initialize optimized caches before starting its long-running process:

```bash
php artisan optimize
```

Laravel deployment optimization is documented at <https://laravel.com/docs/deployment#optimization>.

## Queue worker

Spatie media conversions are queued to the `media` queue. Production requires a continuously running worker:

```bash
php artisan queue:work database \
    --queue=media,default \
    --sleep=3 \
    --tries=3 \
    --backoff=5 \
    --max-time=3600
```

The container restart policy replaces the role normally handled by Supervisor on a non-containerized server.

Verify after each deployment:

- the worker container is running;
- new uploads produce conversions;
- `failed_jobs` does not grow unexpectedly;
- the worker is recreated so it runs the newly deployed code.

## Scheduler

Run exactly one scheduler service:

```bash
php artisan schedule:work
```

Running multiple scheduler instances without overlap controls can duplicate scheduled work.

## Next.js container responsibilities

The Next.js image should use a multi-stage build:

1. Install locked dependencies with `npm ci`.
2. Run linting and TypeScript checks.
3. Run `next build` with standalone output.
4. Copy `.next/standalone`, `.next/static`, and `public` into the runtime stage.
5. Run as a non-root Node user.
6. Listen on `0.0.0.0:3000`.

The reverse proxy, rather than Next.js directly, handles public traffic, TLS, malformed requests, and connection limits.

## Laravel CI pipeline

### Trigger policy

```yaml
push:
  branches:
    - develop
    - main
pull_request:
  branches:
    - develop
    - main
```

### Quality job

The quality job performs:

```text
Checkout exact commit
    |
    v
Install PHP 8.5 and required extensions
    |
    v
Install Composer dependencies from composer.lock
    |
    v
Install Node 22 dependencies with npm ci
    |
    v
Build Vite and Filament assets
    |
    v
Run Pint in check mode
    |
    v
Run PHPStan
    |
    v
Run Pest against SQLite
```

Use explicit CI setup steps rather than `composer setup`. The local setup command performs broad workstation initialization and uses `npm install`; CI requires deterministic `npm ci` behavior.

### PostgreSQL compatibility job

The compatibility job starts a disposable PostgreSQL 17 service, waits for its health check, and then runs:

```bash
php artisan migrate --force
php artisan test
```

It uses CI-only credentials and an empty disposable database.

### CI security

- Pin third-party GitHub Actions to full commit SHAs.
- Let Dependabot update those SHAs.
- Grant `contents: read` by default.
- Do not expose production secrets to pull-request jobs.
- Do not deploy from `pull_request` events.
- Use lockfiles for Composer and npm.

## Next.js CI pipeline

The storefront should expose these quality scripts:

```json
{
  "lint": "eslint",
  "typecheck": "tsc --noEmit",
  "build": "next build",
  "ci:check": "npm run lint && npm run typecheck && npm run build"
}
```

The CI job performs:

```text
Checkout exact commit
    |
    v
Install Node 22
    |
    v
Install dependencies with npm ci
    |
    v
Run ESLint
    |
    v
Run TypeScript checking
    |
    v
Build the production storefront
```

No Vitest, Jest, or Playwright dependency is currently installed. Linting and typechecking must not be described as unit tests. A test framework requires a separate approved implementation.

## Container publishing

After CI passes on `main`, each repository builds and publishes its own image:

```text
ghcr.io/<github-owner>/kokkonis-backend:<git-sha>
ghcr.io/<github-owner>/kokkonis-storefront:<git-sha>
```

Rules:

- Use the full Git commit SHA as the deployment identity.
- A convenience `main` tag may exist, but production records the immutable SHA.
- Use `GITHUB_TOKEN` with `packages: write` for publishing.
- Generate provenance or attestations when available.
- Do not build application code on the production server.

GitHub's container publishing guidance is available at <https://docs.github.com/en/actions/tutorials/publish-packages/publish-docker-images>.

## GitHub production environment

Create a GitHub Environment named `production` in each repository.

Restrict it to `main` and store only deployment access values:

```text
DEPLOY_HOST
DEPLOY_USER
DEPLOY_SSH_KEY
DEPLOY_KNOWN_HOSTS
```

Application secrets remain on Hetzner and are not stored as one large GitHub secret.

When supported by the repository plan, require production approval before environment secrets become available.

Use deployment concurrency:

```yaml
concurrency:
  group: production
  cancel-in-progress: false
```

GitHub environments are documented at <https://docs.github.com/en/actions/reference/workflows-and-actions/deployments-and-environments>.

## Hetzner server baseline

Recommended starting capacity:

- Ubuntu 24.04 LTS;
- x86 server architecture;
- approximately four virtual CPUs;
- approximately eight gigabytes of RAM;
- enough disk for PostgreSQL growth, container images, and temporary processing;
- Hetzner server backups enabled in addition to application backups.

The exact plan should be selected from current Hetzner offerings and monitored after launch. Image conversion, XLSX processing, PostgreSQL, PHP, and Next.js can create short memory peaks.

Install and configure:

- Docker Engine from Docker's official repository;
- Docker Compose plugin;
- a dedicated non-root `deploy` user;
- SSH key authentication;
- disabled SSH password authentication;
- disabled direct root login;
- fail2ban;
- unattended security updates;
- firewall rules;
- log rotation.

Suggested server directory:

```text
/opt/kokkonis/
  compose.yaml
  Caddyfile
  env/
    backend.env
    storefront.env
    postgres.env
  scripts/
    deploy-backend
    deploy-storefront
    backup-postgres
    restore-postgres
  backups/
```

Environment files should have mode `600` and be owned by the deployment operator.

## Network and firewall policy

Public inbound ports:

| Port | Purpose |
|---|---|
| 80 | HTTP redirect and certificate validation |
| 443 | HTTPS traffic |
| 22 | SSH administration and deployment |

Do not expose:

- PostgreSQL 5432;
- Next.js 3000;
- PHP-FPM 9000;
- internal web-service ports.

The reverse proxy is the only public application entry point.

## DNS and hostnames

Recommended hostname pattern:

| Hostname | Target |
|---|---|
| `www.example.gr` | Next.js storefront |
| `backend.example.gr` | Laravel API, health endpoint, and Filament `/admin` |
| `media.example.gr` | Cloudflare R2 custom media domain |

Create DNS records only after the Hetzner server has a stable public IP.

Use HTTPS everywhere. If Cloudflare proxying is enabled, use strict origin certificate validation rather than Flexible SSL.

## First production deployment

### Preparation

1. Confirm both repositories have green CI.
2. Confirm PostgreSQL compatibility tests pass.
3. Confirm production-safe seeders are separated from development seeders.
4. Confirm the Laravel image contains all image-processing dependencies.
5. Confirm the Next.js image uses standalone output.
6. Confirm production R2 media and backup buckets exist.
7. Confirm DNS records point to Hetzner.
8. Confirm server environment files contain no development credentials.
9. Confirm an initial database backup destination is reachable.

### Start infrastructure

```bash
cd /opt/kokkonis
docker compose pull
docker compose up -d postgres
docker compose ps
```

Wait until PostgreSQL is healthy.

### Create schema

```bash
docker compose run --rm backend-php php artisan migrate --force
```

Do not run the default development seeder.

### Start applications

```bash
docker compose up -d
docker compose ps
```

### Create administrator

```bash
docker compose run --rm backend-php php artisan make:filament-user
```

### Verify

Check:

```text
https://backend.example.gr/up
https://backend.example.gr/api/v1/products
https://backend.example.gr/admin
https://www.example.gr/
https://www.example.gr/products
```

Also verify:

- Filament authentication;
- product import;
- product media upload;
- queued image conversions;
- public R2 media delivery;
- catalogue filters;
- missing-product behavior;
- no unpublished products in the API;
- no price, cart, checkout, or payment UI.

## Normal backend deployment

The backend CD procedure is:

```text
Confirm CI success on main
    |
    v
Publish backend image tagged with Git SHA
    |
    v
Connect to Hetzner using the deployment key
    |
    v
Pull the new backend image
    |
    v
Create and verify a pre-deployment database backup
    |
    v
Run migrations using the new image
    |
    v
Recreate backend web, worker, and scheduler services
    |
    v
Verify health, API, queue, and logs
    |
    v
Record deployed SHA
```

## Normal storefront deployment

The storefront CD procedure is:

```text
Confirm CI success on main
    |
    v
Publish storefront image tagged with Git SHA
    |
    v
Connect to Hetzner using the deployment key
    |
    v
Pull the new storefront image
    |
    v
Recreate only the storefront service
    |
    v
Verify homepage, products, images, and server logs
    |
    v
Record deployed SHA
```

Backend contract changes should be deployed before storefront code that depends on them.

## Health checks

Laravel already provides `/up`.

Required post-deployment checks:

```text
GET https://backend.example.gr/up
GET https://backend.example.gr/api/v1/products
GET https://backend.example.gr/api/v1/catalogue/filters
GET https://backend.example.gr/api/v1/storefront
GET https://www.example.gr/
GET https://www.example.gr/products
```

Also inspect:

```bash
docker compose ps
docker compose logs --tail=200 backend-php
docker compose logs --tail=200 backend-worker
docker compose logs --tail=200 storefront
```

The deployment is complete only when functional health checks pass.

## Database backup policy

Hetzner server backups are useful but are not sufficient as the only PostgreSQL backup. Attached Hetzner Volumes are excluded from server backups, and a filesystem snapshot is not a guaranteed transaction-consistent PostgreSQL export.

Use daily logical backups:

```text
pg_dump custom-format archive
    |
    v
Encrypt through the backup tool
    |
    v
Upload to private R2 backup bucket
    |
    v
Verify successful upload
    |
    v
Apply retention rules
```

Recommended retention:

- seven daily backups;
- four weekly backups;
- twelve monthly backups;
- one named backup before every migration deployment.

Use Restic or an equivalent encrypted backup tool configured for the private R2 S3-compatible endpoint.

Hetzner's backup limitations are documented at <https://docs.hetzner.com/cloud/servers/backups-snapshots/overview/>.

## Restore procedure

Test restoration at least quarterly.

The controlled restore procedure is:

1. Put Laravel into maintenance mode or stop write-capable application services.
2. Keep the current damaged database untouched until the restore is verified.
3. Create a new empty PostgreSQL database.
4. Download and decrypt the selected backup.
5. Restore with `pg_restore` into the new database.
6. Point a temporary Laravel container at the restored database.
7. Run `php artisan migrate:status`.
8. Verify administrator login, product counts, categories, brands, attributes, navigation, media records, and API responses.
9. Switch the production database connection only after verification.
10. Bring application services online.
11. Record the incident and the restored backup identifier.

Never discover restore problems for the first time during a production incident.

## Application rollback

Every deployed image is identified by Git SHA.

Application rollback:

```text
Select previous known-good SHA
    |
    v
Update the service image reference
    |
    v
Pull the previous image
    |
    v
Recreate the affected application service
    |
    v
Run health and functional checks
```

Database rollback is not automatically coupled to application rollback. Schema changes must remain compatible with the previous application version. When compatibility is impossible, use a forward migration or a verified database restore.

## Secret inventory

### Server-only Laravel secrets

- `APP_KEY`;
- PostgreSQL password;
- production R2 access key and secret;
- mail credentials;
- future third-party service credentials.

### Server-only PostgreSQL secrets

- PostgreSQL superuser password if enabled;
- application database password;
- backup database credentials.

### GitHub deployment secrets

- deployment host;
- deployment username;
- deployment SSH private key;
- verified SSH host key data.

### Server GHCR credential

The server needs a narrowly scoped token with package-read access for private GHCR images. It does not need repository write access.

### Rules

- Never commit `.env`.
- Never print secrets in workflow logs.
- Never bake secrets into Docker layers.
- Rotate credentials periodically.
- Remove unused credentials.
- Preserve `APP_KEY` securely outside the server for disaster recovery.

## Security baseline

- `APP_DEBUG=false` in production.
- HTTPS-only traffic.
- Secure session cookies.
- PostgreSQL not publicly reachable.
- SSH keys only.
- Root SSH login disabled.
- Password SSH login disabled.
- Fail2ban enabled.
- Automatic security updates enabled.
- GitHub Actions pinned to commit SHA.
- Branch protection on `main`.
- Production deployment secrets protected by a GitHub Environment.
- Separate development and production R2 credentials.
- Least-privilege R2 bucket access.
- Strong administrator credentials.
- Optional Cloudflare Access protection for Filament.
- No secrets in Git, Docker images, browser bundles, or CI logs.

## Monitoring baseline

Monitor:

- Laravel `/up`;
- storefront homepage;
- products endpoint;
- PostgreSQL health;
- disk usage;
- memory usage;
- container restart counts;
- queue failures;
- backup success and age;
- R2 upload/conversion failures;
- HTTP 500 response rate;
- certificate expiration;
- server security updates.

Set Docker log rotation so logs cannot fill the disk.

Minimum alerts:

- website unavailable;
- backend health check unavailable;
- disk above 80 percent;
- PostgreSQL unhealthy;
- backup older than 26 hours;
- repeated queue failures;
- container restart loop.

## Incident runbook

### Storefront unavailable, backend healthy

1. Check `storefront` container status.
2. Inspect storefront logs.
3. Verify `LARAVEL_API_URL` resolves inside the Docker network.
4. Verify the backend API responds from the storefront container.
5. Roll back the storefront image if the failure began after deployment.

### Backend unavailable

1. Check proxy, backend web, and backend PHP containers.
2. Check `/up` internally and externally.
3. Inspect Laravel logs without exposing secrets.
4. Check PostgreSQL health.
5. Check environment-file permissions.
6. Roll back application code only if the database schema remains compatible.

### Media unavailable

1. Verify the public media custom domain.
2. Verify R2 bucket availability.
3. Confirm `AWS_URL` and `AWS_ENDPOINT` are distinct and correct.
4. Confirm the Laravel key has access to the media bucket.
5. Inspect failed conversion jobs.
6. Verify the queue worker processes the `media` queue.

### PostgreSQL unavailable

1. Stop repeated application restarts if they increase load.
2. Check container health and PostgreSQL logs.
3. Check disk space and volume availability.
4. Avoid deleting or recreating the database volume.
5. Restore into a new database when data recovery is necessary.

### Failed migration

1. Stop the deployment.
2. Keep the previous application version available.
3. Inspect the migration error.
4. Do not blindly run rollback.
5. Determine whether the failed migration committed partial work.
6. Apply a safe forward fix or restore the verified pre-deployment backup.

## Go-live checklist

### Code and data

- [ ] Laravel CI passes.
- [ ] Laravel PostgreSQL compatibility job passes.
- [ ] Next.js lint passes.
- [ ] Next.js typecheck passes.
- [ ] Next.js production build passes.
- [ ] Default production seeding cannot create test users.
- [ ] All migrations apply to an empty PostgreSQL database.
- [ ] Relevant migration rollback tests pass.
- [ ] API exposes no unpublished products.
- [ ] API contains no prices or live stock quantities.

### Infrastructure

- [ ] Hetzner server is provisioned and patched.
- [ ] Docker and Compose are installed.
- [ ] Non-root deployment user exists.
- [ ] Firewall permits only required ports.
- [ ] PostgreSQL is private.
- [ ] Docker volumes are present.
- [ ] Container log rotation is enabled.
- [ ] DNS resolves correctly.
- [ ] HTTPS certificates are valid.

### Secrets

- [ ] Production `APP_KEY` is generated once and backed up securely.
- [ ] PostgreSQL password is strong.
- [ ] Production R2 media credentials are restricted.
- [ ] Backup R2 credentials are separate and restricted.
- [ ] GitHub production environment is configured.
- [ ] Deployment SSH host key is verified.
- [ ] No `.env` file is committed.

### Application

- [ ] Laravel `/up` responds successfully.
- [ ] Filament login works.
- [ ] First administrator uses a unique password.
- [ ] Product import works.
- [ ] Product images upload to production R2.
- [ ] Media conversions complete through the queue.
- [ ] Storefront homepage renders.
- [ ] Products page renders real API data.
- [ ] Filters and sorting work.
- [ ] Missing product routes return the intended response.
- [ ] Mobile layout is usable.

### Recovery

- [ ] Initial production backup succeeds.
- [ ] Backup is visible in the private backup bucket.
- [ ] Restore procedure has been tested.
- [ ] Previous image SHA is recorded.
- [ ] Rollback procedure is documented for the operator.

## Routine operations

### Before every release

- Review dependency updates.
- Confirm CI passes in both repositories.
- Review migrations manually.
- Confirm API compatibility.
- Confirm production environment changes are documented.
- Confirm backup health.

### After every release

- Record deployed Git SHA values.
- Verify Laravel health.
- Verify storefront health.
- Verify queue and scheduler containers.
- Inspect fresh logs.
- Confirm no failed jobs.
- Confirm product media remains accessible.

### Weekly

- Review Dependabot pull requests.
- Review failed jobs.
- Review container restarts.
- Review disk and memory trends.
- Confirm recent backups.

### Monthly

- Apply server security updates.
- Review secrets and access.
- Review R2 lifecycle behavior.
- Remove unused old container images while preserving rollback targets.
- Review PostgreSQL growth.

### Quarterly

- Perform a database restore drill.
- Review firewall and SSH access.
- Rotate eligible credentials.
- Review whether one-server capacity remains sufficient.

## Scaling path

Scale only when measurements justify it.

Recommended order:

1. Increase Hetzner server resources.
2. Add Redis for cache, sessions, and queues.
3. Move PostgreSQL to a dedicated or managed database service.
4. Add a second application server behind a load balancer.
5. Add shared Next.js cache coordination when multiple storefront instances exist.
6. Reconsider orchestration only after Compose and a single server are genuine constraints.

Kubernetes is not part of the current plan.

## Docker and CI/CD learning path

### Stage 1: Image fundamentals

Learn:

- image versus container;
- build context;
- layers and cache;
- multi-stage builds;
- `.dockerignore`;
- non-root users;
- image tags and digests.

Practice by building each application locally without deploying it.

### Stage 2: Compose fundamentals

Learn:

- services;
- networks;
- named volumes;
- environment files;
- health checks;
- restart policies;
- one-off commands;
- container logs.

Practice by running Laravel, Next.js, and PostgreSQL locally in a production-like Compose project while retaining normal non-Docker development for daily Mac work.

### Stage 3: CI fundamentals

Learn:

- workflow triggers;
- jobs and steps;
- dependency caches;
- artifacts;
- permissions;
- protected branches;
- deterministic installs;
- action SHA pinning.

### Stage 4: Image publishing

Learn:

- GHCR authentication;
- immutable SHA tags;
- build cache;
- provenance;
- private package pull credentials.

### Stage 5: Controlled deployment

Learn:

- GitHub Environments;
- deployment concurrency;
- server-owned secrets;
- SSH host verification;
- health-gated deployment;
- rollback to a known image SHA.

### Stage 6: Operations

Learn:

- PostgreSQL backup and restore;
- log rotation;
- disk monitoring;
- queue supervision;
- certificate renewal;
- incident response;
- capacity planning.

## Official references

- Laravel deployment: <https://laravel.com/docs/deployment>
- Laravel queues: <https://laravel.com/docs/queues>
- Laravel task scheduling: <https://laravel.com/docs/scheduling>
- Next.js deployment: <https://nextjs.org/docs/app/getting-started/deploying>
- Next.js self-hosting: <https://nextjs.org/docs/app/guides/self-hosting>
- Docker Compose production: <https://docs.docker.com/compose/how-tos/production/>
- GitHub container publishing: <https://docs.github.com/en/actions/tutorials/publish-packages/publish-docker-images>
- GitHub deployment environments: <https://docs.github.com/en/actions/reference/workflows-and-actions/deployments-and-environments>
- Hetzner backups and snapshots: <https://docs.hetzner.com/cloud/servers/backups-snapshots/overview/>
- Cloudflare R2 lifecycle rules: <https://developers.cloudflare.com/r2/buckets/object-lifecycles/>

## Final operating principle

Development optimizes for speed and safety. CI proves repeatability and PostgreSQL compatibility. Production optimizes for durability, security, observability, and recoverability.

A release is complete only when:

- both applications are healthy;
- migrations have completed safely;
- queues process correctly;
- media is accessible;
- the deployed Git SHA is recorded;
- a valid backup exists;
- rollback remains possible.
