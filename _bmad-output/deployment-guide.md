# Deployment Guide

## Docker-based Runtime

- `docker-compose.yml` (PHP 8.1) or `docker-compose-8.0.yml` (PHP 8.0)
- Services: nginx, php-fpm, mysql
- Exposes HTTP on `http://localhost:8080`

## Environment Variables

- Required: `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_HOST`, `DB_PORT`
- Optional: `APP_ENV`, `APP_DEBUG`, `TEST_HOST`

## Notes

- No CI/CD pipeline is included; deployment is manual via Docker Compose.
