# Changelog

## v1.5.2
* Fix TestLogger to be compatible with symfony 5.4 LTS.

## v1.5.1
* Bugfix: kernel not injected into JsonContext

## v1.5.0

### Restructuring BrowserContext
As the BrowserContext was getting more and more complex, it is now split into 4 Contexts, sharing the state:
* HttpContext - Basic request/response operations, including headers and redirects
* HtmlContext - Querying the DOM for Text/HTML
* FromContext - Testing, manipulating and submitting forms
* JsonContext - Json response checks for custom APIs

### Switching LoggingContext
The LoggingContext was formerly bound to monolog, which is not required in newer symfony applications.
Instead, we now have a PSR-compatible TestLogger that catches the logs and can be queried in behat.

### Improvements to HttpContext
* Allow adding HTTP headers to `When I send ... request to ...`
* Allow non-rfc conform redirects with path instead of URL
* Allow testing for path only in redirect check

### Improvements to HtmlContext
* `When I remove attribute ... from ...` to unhide elements
* `Then I see ... before ...` to check sortings.
* TODO: Not found tags are not shown with full content, but only the tag

### Improvements to FormContext
* `When I submit the form with button ...` to distinguish the submit button
* `When I clear field ...` to remove data from a field
* `When I check ... checkbox with value ...` to distinguish between multi-options
* `When I uncheck ... checkbox` to revert checking
* `When I add an input field ...` to add dynamic collection fields 
* `When I remove an input field ...` to remove dynamic collection fields 
* `When I remove a select field ...` to remove dynamic collection fields
* `Then the form contains a select` to check for options in selects
* `Then select ... contains option` to check for options in selects
* `Then select ... does not contain option` to check for options in selects
* TODO: file upload

### Improvements in AbstractDatabaseContext
* `assertObject` returns the found object to allow steps to be built upon the results.
* Support for constructor arguments, including defaults defined in the context.
* `assertCollectionDoesNotContain` helps negation of collection check
* Support creating m:n relations by adding a `$reverseRelationName` parameter.

### Added DateContext
Based on [ClockMock](https://packagist.org/packages/slope-it/clock-mock) and the uopz extension, the context allows you to change the current date inside your tests.

### Added MailerContext in favour of SwiftmailerContext
As Swiftmailer is deprecated in favour of the symfony mailer, we adapted the context as well.

### Added AbstractApiContext
This context will help you build your own API context, like the AbstractDatabaseContext

## v1.4.2

* Fix: Comparing a string to a tag with inner html that contains spaces.

## v1.4.1

* Fix: Error, when a monolog handler is tagged.

## v1.4.0

* Support for PHP 8.2 and Symfony 6.2

## v1.3.0
### Added support for multiselect
`<select multiple>` form fields can now be selected with
```gherkin
And I select "option1,option2" from "hello[multiselect]"
```

### Added check for HTTP headers 
Check the HTTP for headers, including regex compare with `~` at the beginning
```gherkin
Then the response has http headers
  | location | ~/oauth2/authorize |
  | Pragma | no-cache |
```

### Consider HTTP/303 and 308 as valid redirect, too.
This now passes, too if the response code is 303 or 308  
```gherkin
Then I am being redirected to "/test"
```

### Allow regex in some more assertions
```gherkin
Then I am being redirected to "~/test"
Then I see an a tag "~Hello.*"
Then the form contains an input field "~test\[.*_name\]"
Then the command outputs "~Hello"
```

### Negation of command output
```gherkin
Then the command does not output "~Hello"
```

### AbstractDatabaseContext reloaded
The new Abstraction should bring everything you need to create custom contexts based on entities.
You can fully focus upon the business language and let the abstraction do the work for you.
```php
/**
 * @implements AbstractDatabaseContext<Entry>
 */
class MyContext extends AbstractDatabaseContext
{
    #[BeforeScenario]
    public function reset(): void
    {
        // Delete all entries and reset the auto_increment/sequence to 0
        $this->resetDatabase();
        $this->resetSequence();
        // Also reset other (m:n relation) tables
        $this->resetDatabase('another_entity');
    }

    #[Given('there is an entry')]
    public function thereIsAnEntry(TableNode $table): void
    {
        $this->createObject($table);
    }

    #[Given(':id1 is related to :id2')]
    public function isRelatedTo(int $id1, int $id2): void
    {
        $this->createRelation($id1, AnotherEntity::class, $id2, 'relatedTo');
    }

    #[Then('there exists an entry')]
    public function thereExistsAnEntry(TableNode $table): void
    {
        $this->assertObject($table);
    }

    #[Then('there exists no entry')]
    public function thereExistsNoEntry(TableNode $table): void
    {
        $this->assertNoObject($table);
    }
    
    #[Then(':id1 is now related to :id2')]
    public function isNowRelatedTo(int $id1, int $id2): void
    {
        $this->assertCollectionContains($id1, AnotherEntity::class, $id2, 'relatedTo');
    }

    protected function getClassName(): string
    {
        return MyEntity::class;
    }
```
