# Development Guide

## Prerequisites

- Docker and Docker Compose
- (Optional) PHP 8.x + Composer for local runs

## Environment

- Copy `.env.dist` to `.env` if running locally.
- Docker stack injects DB/Test variables via compose files.

## Setup

- `make init` (PHP 8.1) or `make init-8.0`
- `make composer-install`

## Run

- `make up` then open `http://localhost:8080`

## Tests

- `make test`
- `composer test` (local)

## Code Quality

- `make lint`, `make fixcs`, `make rector`
