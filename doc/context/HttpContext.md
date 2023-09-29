# HttpContext
Performs HTTP Operations (Request/Response).

## When
### `When I visit <url>`
Open a page.
### `When I navigate to <url> with http headers`
Open a page but add some headers (from table) in the request.
### `When I make a <method> request to <url>`
Perform a request with a different method and optional json data.
### `When I follow the redirect`
Visit the page that is set in the location header of the previous response.

## Then
### `Then the response status code is <code>`
Check the HTTP Status code.
### `Then the page shows up`
Alias for status code 200.
### `Then the response has http headers`
Check for the existence of certain HTTP Headers.
### `Then I am being redirected to <url>`
Check if the status code and location header are set.
