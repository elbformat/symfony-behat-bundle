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

.PHONY: sf-5
sf-5:
	docker compose run --rm -e SYMFONY_REQUIRE="5.4.*" php composer update
.PHONY: sf-6
sf-6:
	docker compose run --rm -e SYMFONY_REQUIRE="6.4.*" php composer update
.PHONY: sf-7
sf-7:
	docker compose run --rm -e SYMFONY_REQUIRE="7.4.*" php composer update
.PHONY: orm-2
orm-2:
	$(DOCKER_RUN) composer update --with "doctrine/orm:^2.0" doctrine/orm -W
.PHONY: orm-3
orm-3:
	$(DOCKER_RUN) composer update --with "doctrine/orm:^3.0" doctrine/orm -W

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