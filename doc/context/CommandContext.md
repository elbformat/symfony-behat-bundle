# CommandContext
This context contains steps to test symfony commands

## When
### `When I run command <command>`
Execute the given symfony command, including parsing of arguments.

## Then
### `Then the command has a return value of <code>`
Check the return code. 0 indicates a success, everything else an error.
### `Then the command outputs <text>`
Check if the text is contained somewhere in the command's output.
### `Then the command does not output <text>`
Make sure, that the given text is not in the command's output.