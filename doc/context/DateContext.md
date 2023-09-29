# DateContext
This context manipulates the time. It uses the uopz extension and [ClockMock](https://packagist.org/packages/slope-it/clock-mock).

## Given
### `Given the current date is <date>`
Modify the date, that is returned by `date()` or `new DateTime()`.
Will automatically be reset to the current date before each scenario.
