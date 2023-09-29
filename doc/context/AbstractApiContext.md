# AbstractApiContext
This context has no steps by itself, but helper to mock away external APIs in tests and check if they are called correctly, or how the app behaves with different responses.
For example if you want to test your connection to an API for users  

## Given
### `Given a call to the user api <url> will return`
```
"""
{"some":"json response"}
"""
```
or 
```
"""
HTTP/1.0 OK
Content-Type: application/json
 
{"some":"json response with http headers"}
"""
```
Will mock the response, the next request to his api will get.
Backed by using the `addResponse` method.

## Then
### `the user api <url> has been called`
Check if the api call was performed right, by the application.
Can also contain payload or methods.
Backed by using the `assertApiCall` method.
### `the user api <url> has not been called`
The opposite, by using `assertNoApiCall` method.
