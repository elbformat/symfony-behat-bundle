# LoggingContext
Checks if logging works as expected. 
Uses a MockLogger under the hood.
Is independent of monolog as it's using the PSR Logger.

## Then
### `Then the log contains a(n) <level> entry <text>`
Check if the given message wit the given severity exists.
Can optionally also check for the logging context via table.
