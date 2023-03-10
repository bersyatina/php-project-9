start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

db-reset:
	dropdb railway || true
	createdb railway

create_tables:
	psql railway < database.sql

install:
	composer install

PORT ?= 8000

validate:
	composer validate

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src bin