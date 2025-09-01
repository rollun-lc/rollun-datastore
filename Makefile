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

docker-build:
	docker compose build

composer-install:
	docker compose exec -T rollun-datastore-php-fpm composer install

composer-test:
	docker compose exec -T rollun-datastore-php-fpm composer test

wait-db:
	docker compose exec rollun-datastore-php-fpm wait-for-it rollun-datastore-mysql:3306 -t 30