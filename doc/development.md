# How to develop this bundle?

## Project Overview

`elbformat/symfony-behat-bundle` provides reusable, pre-built Behat contexts for testing Symfony applications. The goal is to let developers drop in step definitions for common testing scenarios (HTTP, forms, JSON, commands, mailer, logging, etc.) without writing boilerplate.

**Requirements:**
- PHP >= 8.3 (`ext-dom`, `ext-json` required)
- Symfony 7.4.*
- Behat >= 3.8
- `friends-of-behat/symfony-extension` >= 2.2

**Optional runtime dependencies** (enable additional contexts):
- `symfony/framework-bundle` → `CommandContext`
- `symfony/mailer` → `MailerContext`
- `slope-it/clock-mock` → `DateContext` (also requires `ext-uopz`)

---

## Project Structure

```
symfony-behat-bundle/
├── src/
│   ├── ElbformatSymfonyBehatBundle.php     # Bundle class; registers TestLoggerCompilerPass
│   ├── Application/
│   │   └── ApplicationFactory.php          # Creates Console\Application for CommandContext
│   ├── Browser/
│   │   └── State.php                       # Carries request/response across Behat steps
│   ├── Context/
│   │   ├── AbstractApiContext.php          # Base class for custom external-API contexts
│   │   ├── AbstractDatabaseContext.php     # Base class for custom database contexts
│   │   ├── CommandContext.php              # Test Symfony console commands
│   │   ├── DateContext.php                 # Freeze/mock the current date
│   │   ├── FormContext.php                 # Fill and submit HTML forms
│   │   ├── HtmlContext.php                 # Assert DOM elements and text
│   │   ├── HttpContext.php                 # Make HTTP requests and assert responses
│   │   ├── JsonContext.php                 # Send/check JSON in requests and responses
│   │   ├── LoggingContext.php              # Assert log entries
│   │   ├── MailerContext.php               # Assert sent emails
│   │   ├── DomTrait.php                    # Shared DOM helpers (getCrawler, etc.)
│   │   ├── RequestTrait.php                # Shared HTTP request handling (doRequest, buildRequest)
│   │   └── TableTrait.php                  # Converts Behat TableNode to associative array
│   ├── DependencyInjection/
│   │   ├── ElbformatSymfonyBehatExtension.php  # Loads services.yml; conditionally registers contexts
│   │   └── TestLoggerCompilerPass.php          # Aliases 'logger' service to TestLogger
│   ├── Helper/
│   │   ├── ArrayDeepCompare.php            # Deep recursive array comparison for JSON assertions
│   │   └── StringCompare.php              # String matching (supports ~ for regex prefix)
│   ├── HttpClient/
│   │   └── MockClientCallback.php          # Mock HTTP client callback for external API testing
│   ├── Logger/
│   │   ├── LogEntry.php                    # Value object representing a single log entry
│   │   └── TestLogger.php                  # Captures log entries for assertions
│   ├── Mailer/
│   │   ├── TestTransport.php               # Captures sent emails during tests
│   │   └── TestTransportFactory.php        # Factory tagged as mailer.transport_factory
│   └── Resources/config/
│       └── services.yml                    # Core service definitions with autoconfigure/autowire
├── tests/
│   ├── Application/                        # Unit tests for ApplicationFactory
│   ├── Browser/                            # Unit tests for Browser\State
│   ├── Context/                            # Unit tests for each context class (14 test classes)
│   ├── Helper/                             # Unit tests for ArrayDeepCompare and StringCompare
│   └── fixtures/                           # Test fixtures and dummy context implementations
├── doc/
│   ├── context/                            # Per-context step definition documentation
│   ├── changelog.md
│   ├── development.md                      # This file
│   └── examples.md                         # Example Gherkin scenarios
├── .circleci/config.yml                    # CI/CD pipeline definition
├── composer.json
├── phpunit.xml
└── psalm.xml                               # Psalm config
```

---

## Architecture

### Bundle Bootstrap

The bundle is registered via `ElbformatSymfonyBehatBundle`, which extends Symfony's `Bundle`. Its only job is to add a compiler pass:

```php
// src/ElbformatSymfonyBehatBundle.php
$container->addCompilerPass(new TestLoggerCompilerPass());
```

`TestLoggerCompilerPass` aliases the `logger` service to `TestLogger::class` so that `LoggingContext` can capture and assert on log entries emitted during a test.

### Service Registration

`ElbformatSymfonyBehatExtension::load()` does two things:

1. **Loads `src/Resources/config/services.yml`** — registers all unconditional services with `autoconfigure: true` and `autowire: true`. This covers all contexts that have no optional dependencies (`HttpContext`, `FormContext`, `HtmlContext`, `JsonContext`, `LoggingContext`) plus supporting services (`Browser\State`, helpers, `TestLogger`, etc.).

2. **Conditionally registers optional contexts** by checking for the presence of a class at load time:

   | Class checked | Context registered |
   |---|---|
   | `Symfony\Bundle\FrameworkBundle\Console\Application` | `CommandContext` |
   | `Symfony\Component\Mailer\Mailer` | `MailerContext` |
   | `SlopeIt\ClockMock\ClockMock` | `DateContext` |

   This means you only get a context if the corresponding package is installed — no configuration required.

### Request/Response State

`Browser\State` is the central shared object that carries HTTP state across steps within a Behat scenario. It holds:
- The current `Request` object
- The current `Response` object
- Cookies accumulated from `Set-Cookie` headers
- A lazily-created `DOMCrawler` for DOM assertions

`RequestTrait` (used by `HttpContext`, `FormContext`, `JsonContext`) calls `doRequest()`, which reboots the Symfony kernel and dispatches the request through it. This keeps each request isolated while preserving cookies between redirects.

### Shared Traits

| Trait | Purpose |
|---|---|
| `RequestTrait` | `doRequest()` / `buildRequest()` — kernel reboot + cookie handling |
| `DomTrait` | `getCrawler()` — returns the `DOMCrawler` from the current response |
| `TableTrait` | `getTableData()` — converts a Behat `TableNode` to an associative array |

---

## Development Environment Setup

Docker is used for local development.

```bash
# Start a shell inside the PHP container
docker compose run php sh

# Install dependencies
composer install
```

---

## Running Tests and Code Quality Tools

All commands are run inside the Docker container unless noted otherwise.

### Unit Tests

```bash
vendor/bin/phpunit
```

Tests are located in `tests/`. The `unit` test suite (defined in `phpunit.xml`) excludes integration tests.

To generate a coverage report:
```bash
phpdbg -qrr -d memory_limit=4G vendor/bin/phpunit --testsuite unit
# HTML report: build/coverage/html/
# Clover XML:  build/clover.xml
```

> **Note:** `DateContext` tests require the `uopz` PHP extension (used by `slope-it/clock-mock` for date mocking). Install it with:
> ```bash
> apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
>   && pecl install uopz-7.1.1 \
>   && apk del .build-deps \
>   && docker-php-ext-enable uopz
> ```

### Code Style

```bash
vendor/bin/php-cs-fixer fix --diff src
vendor/bin/php-cs-fixer fix --diff tests
```

In CI, `--dry-run` is added to fail on violations without modifying files.

### Static Analysis (Psalm)

```bash
vendor/bin/psalm
```

Psalm runs at error level 1 (strictest).

### Xdebug (inside container)

```bash
docker-php-ext-enable xdebug
export XDEBUG_CONFIG="client_host=172.17.0.1 idekey=PHPSTORM"
export XDEBUG_MODE="debug"
```

---

## CI/CD (CircleCI)

The pipeline is defined in `.circleci/config.yml` (version 2.1). It uses a **matrix strategy** across PHP and Symfony versions.

**Matrix:**
- PHP: `8.3`, `8.4`
- Symfony: `7.4`
- Monolog: `2.6`

**Jobs (run in this order per matrix combination):**

1. **`build`** — Checks out the repo, installs `uopz`, pins Symfony and Monolog versions, installs Composer dependencies, and persists the `vendor/` directory to the workspace.
2. **`phpunit`** — Attaches the workspace, runs the unit test suite via `phpdbg`, stores JUnit results, and uploads coverage to Codecov.
3. **`php-cs-fixer`** — Runs only once (PHP 8.1 / SF 5.4). Checks `src/` and `tests/` in dry-run mode.
4. **`psalm`** — Runs static analysis using `psalm.xml`.

---

## How to Add a New Context

1. **Create the context class** in `src/Context/MyContext.php`. Use the existing contexts as a reference for constructor injection and step definition patterns.

2. **Register the service:**
   - If the context has **no optional dependencies**, it is auto-registered by `services.yml` (because the whole `src/` namespace is loaded with `autoconfigure: true`). No further action needed.
   - If the context requires an **optional dependency** (e.g. a package that may not be installed), add a conditional registration in `ElbformatSymfonyBehatExtension::load()`:
     ```php
     if (class_exists('Vendor\\Package\\SomeClass')) {
         $def = new Definition(MyContext::class);
         $def->setAutoconfigured(true);
         $def->setAutowired(true);
         $container->setDefinition(MyContext::class, $def);
     }
     ```

3. **Write unit tests** in `tests/Context/MyContextTest.php`. Mock `KernelInterface` and `Browser\State` as needed; see existing tests for patterns.

4. **Write documentation** in `doc/context/MyContext.md` describing each step definition.

5. **Link the new context** from `README.md` in the Features list.
