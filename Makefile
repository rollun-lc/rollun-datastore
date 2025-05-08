# load .env
ifneq (,$(wildcard .env))
    include .env
    export
endif

init: docker-down-clear docker-pull docker-build docker-up composer-install wait-db
up: docker-up
down: docker-down
restart: docker-down docker-up
test: composer-test

docker-up:
	docker compose up -d

docker-down:
	docker compose down --remove-orphans

docker-down-clear:
	docker compose down -v --remove-orphans

docker-pull:
	docker compose pull

# Set UID and GID dynamically but allow overrides
DOCKER_USER_UID ?= $(shell id -u)
DOCKER_USER_GID ?= $(shell id -g)

export DOCKER_USER_UID
export DOCKER_USER_GID

docker-build:
	docker compose build --build-arg WWW_DATA_UID=$(DOCKER_USER_UID) --build-arg WWW_DATA_GID=$(DOCKER_USER_GID)

php:
	docker compose exec -it php-fpm /bin/bash

php-root:
	docker compose exec -u root -it php-fpm /bin/bash

composer-install:
	docker compose exec -T php-fpm composer install

composer-test:
	docker compose exec -T php-fpm composer test

wait-db:
	docker compose exec php-fpm wait-for-it mysql:3306 -t 30