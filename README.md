# symfony-behat-bundle
This bundle provides reusable behat contexts for symfony applications.
All steps are written in present tense, as suggested on https://automationpanda.com/2021/05/11/should-gherkin-steps-use-past-present-or-future-tense/

## Features
* [CommandContext](doc/context/CommandContext.md) to test symfony commands.
* [DateContext](doc/context/DateContext.md) to mock the current date.
* [FormContext](doc/context/FormContext.md) to test/manipulate/fill html forms.
* [HtmlContext](doc/context/HtmlContext.md) to check the DOM/plain HTTP response.
* [HttpContext](doc/context/HttpContext.md) for simple HTTP interactions.
* [JsonContext](doc/context/JsonContext.md) to send or check json data in request/response.
* [LoggingContext](doc/context/LoggingContext.md) to verify correct logging.
* [MailerContext](doc/context/MailerContext.md) to check if mails were triggered.
* [AbstractApiContext](doc/context/AbstractApiContext.md) to help implementing custom context for external API interaction.
* [AbstractDatabaseContext](doc/context/AbstractDatabaseContext.md) to help implementing custom context with database interaction.

## Installation

Add to your composer requirements as dev dependency.
```console
$ composer require --dev elbformat/symfony-behat-bundle
```

Activate bundle in your `config/bundles.php`
```php
Elbformat\SymfonyBehatBundle\ElbformatSymfonyBehatBundle::class => ['test' => true],
```

Use contexts in your `behat.yml` as you like
```yaml
default:
  extensions:
    FriendsOfBehat\SymfonyExtension:
      bootstrap: tests/bootstrap.php
      kernel:
        path: src/Kernel.php
        class: App\Kernel
        environment: behat
        debug: false
  suites:
    default:
      contexts:
        - Elbformat\SymfonyBehatBundle\Context\CommandContext
        - Elbformat\SymfonyBehatBundle\Context\DateContext
        - Elbformat\SymfonyBehatBundle\Context\FormContext
        - Elbformat\SymfonyBehatBundle\Context\HtmlContext
        - Elbformat\SymfonyBehatBundle\Context\HttpContext
        - Elbformat\SymfonyBehatBundle\Context\JsonContext
        - Elbformat\SymfonyBehatBundle\Context\LoggingContext
        - Elbformat\SymfonyBehatBundle\Context\MailerContext
```
### Mailer
To make the Test-Mailer work, you need to set the mailer dsn in `config/packages/mailer.yaml`
```
when@test:
    framework:
        mailer:
            dsn: 'test://test'

```
### API
To not send requests to a real api, you should configure the MockClient to be used in `config/packages/framework.yaml`
```
when@test:
    framework:
        http_client:
            mock_response_factory: App\Tests\Mock\MockClientCallback
```         

## Examples
Examples can be found in [dock/examples.md](doc/examples.md).

## Updating
When updating from a previous version, see the [changelog](doc/changelog.md) for changes. 

## What's next?
The next release should likely contain more tests/stability improvements.

## Development
If you want to develop on the bundle you will find useful information in [doc/development.md](doc/development.md)