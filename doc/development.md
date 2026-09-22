# How to develop this bundle?

There is a Makefile for basic tasks, that uses docker under the hood
```bash
# Install dependencies
make composer
# Run unittests
make phpunit
# Fix styling
make php-cs-fixer
# Check code
make phpstan
# Open shell
make shell
# Revert local changes
make clean
```

## use xdebug
You need to enable xdebug inside the container
```bash
make shell
docker-php-ext-enable xdebug
export XDEBUG_CONFIG="client_host=172.17.0.1 idekey=PHPSTORM"
export XDEBUG_MODE="debug"
```
Alternatively you can configure the environment in a compose.override.yaml
```yaml
services:
  php:
    environment:
      XDEBUG_CONFIG: "client_host=host.docker.internal log_level=0"
      XDEBUG_MODE: "debug"
      DBGP_IDEKEY: "PHPSTORM"
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

## Build docker images
The docker images are build periodically in github. 
If you need to test the build locally (after changes), you can do it with
```bash
make docker
```

## Run different php versions
The easiest way is to create a compose.override.yaml
```yaml
services:
  php:
    image: ghcr.io/elbformat/symfony-behat-bundle/php:8.4
```