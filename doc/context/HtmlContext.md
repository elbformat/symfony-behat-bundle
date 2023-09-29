# HtmlContext
Performs checks on the plain response or DOM.

## When
### `When I remove attribute <attr> from <xpath>`
Modify the DOM to remove attributes like "disabled".

## Then
### `Then I see <text>`
Check if the string is contained somewhere in the plain text response.
### `Then I don't see <text>`
Check if the string is not contained somewhere in the plain text response.
### `Then I see :text1 before :text2`
Check if the string comes before the other string. Useful to test sorting.
### `Then I see a(n) :tag tag <text>`
Check for a specific html tag. Attributes can be defined in a table. Text is optional and matched against the strip_tags content. 
### `Then I don\'t see a(n) :tag tag :content`
The opposite.