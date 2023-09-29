# How to develop this bundle?

Use docker for local tasks
```bash
docker run -it -v $(pwd)/:/var/www $(docker build -q . -f docker/Dockerfile.php) sh
# Install dependencies
composer install
# Run behat tests
vendor/bin/phpunit
# Fix styling
vendor/bin/php-cs-fixer fix --diff src
vendor/bin/php-cs-fixer fix --diff tests
# Check code
vendor/bin/psalm
```

Enable xdebug inside the container
```bash
docker-php-ext-enable xdebug
export XDEBUG_CONFIG="client_host=172.17.0.1 idekey=PHPSTORM"
export XDEBUG_MODE="debug"
```