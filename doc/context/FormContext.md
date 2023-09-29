# FormContext
Although it's technically and logically close to the HtmlContext, the form thing have an extra context.
This is because of the high complexity and huge amount of steps it contains.

## When
### `When I use form <name>`
Bind all following steps to this named form.
### `When I fill <value> into <fieldName>`
Fill an input field.
### `When I select <fieldName> radio button with value <value>`
Select a radio button.
### `When I clear field <fieldName>`
Reset the value of an input field.
### `When I check <fieldName> checkbox`
Check a single checkbox
### `When I check <fieldName> checkbox with value <value>`
Check an array checkbox 
### `When I uncheck <fieldName> checkbox`
Uncheck a single checkbox.
### `When I uncheck <fieldName> checkbox with value <value>`
Uncheck an array checkbox
### `When I select <value> from <fieldName>`
Select from a dropdown.
### `When I select <fixture> upload at <fieldName>`
Select a file upload.
### `When I add an input field <fieldName>`
Add a new field to the form.
### `When I remove an input field <fieldName>`
Remove a field from the form.
### `When I remove a select field <fieldName>`
Remove a dropdown from the form.
### `When I submit the form`
Submit the form without a button.
### `When I submit the form with button <buttonName>`
Submit the form with a specific button.

## Then
### `Then the page contains a form named <name>`
Check existence of the form.
### `Then the form contains an input field`
Check existence of the field (and it's attributes).
### `Then the form contains a select`
Check existence of the dropdown.
### `Then select <fieldName> contains option <label>`
Check existence of a dropdown option.
### `Then select <fieldName> does not contain option`
Check absence of a dropdown option.
