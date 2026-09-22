ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))
DOCKER_RUN ?= docker compose run --rm php

.PHONY: php-cs-fixer
php-cs-fixer:
	docker run -v "$(ROOT_DIR):/code" --rm ghcr.io/php-cs-fixer/php-cs-fixer:3.95-php8.3 fix --diff

.PHONY: phpstan
phpstan:
	$(DOCKER_RUN) vendor/bin/phpstan --memory-limit=-1

.PHONY: phpunit
phpunit:
	$(DOCKER_RUN) vendor/bin/phpunit --coverage-html=build

.PHONY: composer
composer:
	$(DOCKER_RUN) composer install

.PHONY: shell
shell:
	docker compose run -it --rm php sh

.PHONY: docker
docker:
	docker build -t ghcr.io/elbformat/symfony-behat-bundle/php:8.3 . -f docker/Dockerfile.8.3
	docker build -t ghcr.io/elbformat/symfony-behat-bundle/php:8.4 . -f docker/Dockerfile.8.4
	docker build -t ghcr.io/elbformat/symfony-behat-bundle/php:8.5 . -f docker/Dockerfile.8.5

.PHONY: clean
clean:
	rm -Rf build .php-cs-fixer.cache .phpunit.result.cache
	rm -f composer.lock symfony.lock
	git checkout -- composer.json
	git checkout -- .gitignore