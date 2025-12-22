# Development Instructions

## Prerequisites

- Docker and Docker Compose
- (Optional) PHP 8.x + Composer if running outside Docker

## Environment Setup

- Copy `.env.dist` to `.env` and set values if running locally.
- Docker compose provides default DB settings via environment variables.

## Common Commands (Makefile)

- `make init` — full Docker setup + composer install (PHP 8.1)
- `make init-8.0` — full Docker setup + composer install (PHP 8.0)
- `make up` / `make down` — start/stop Docker stack
- `make restart` — restart Docker stack
- `make php` — shell into php-fpm container
- `make php-root` — shell into php-fpm as root

## Dependency Install

- Docker: `make init` (or `make init-8.0`)
- Composer inside container: `make composer-install`

## Run / Local Dev

- Docker stack exposes nginx on `http://localhost:8080`.
- Default web entry: `public/index.php`.

## Tests

- `make test` (runs `composer test` in container)
- `composer test` (local, if PHP/Composer installed)

## Code Quality

- `make lint` — PHP-CS-Fixer dry run
- `make fixcs` — PHP-CS-Fixer apply fixes
- `make rector` — Rector dry run
