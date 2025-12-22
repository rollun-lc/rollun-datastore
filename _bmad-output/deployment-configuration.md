# Deployment Configuration

## Docker Compose

- `docker-compose.yml` (PHP 8.1): nginx + php-fpm + mysql + csfixer
- `docker-compose-8.0.yml` (PHP 8.0): nginx + php-fpm + mysql + csfixer

### Services

- **nginx**: serves `/var/www/app`, exposes port `8080:80`
- **php-fpm**: PHP runtime, mounts project root, uses env for DB and test host
- **mysql**: MySQL 8.2 with data volume
- **csfixer**: php-cs-fixer image for lint/fix

### Environment Variables (examples)

- `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_HOST`, `DB_PORT`
- `TEST_HOST`
- `APP_ENV`, `APP_DEBUG` (from `.env` if present)

## CI/CD

- No CI pipeline definitions found in repo.
