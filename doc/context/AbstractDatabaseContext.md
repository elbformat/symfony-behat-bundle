# AbstractDatabaseContext
This context has no steps by itself, but helper to create an own context by extending it.
For example if you want to create users and assign them to groups this could look like.

## Given
### `Given there is a user`
```
| firstname | Test |
| lastname  | User |
```
Create the entity by mapping attributes from the table. 
Uses `createObject` under the hood.
### `Given the user <userId> is assigned to group <groupId>`
Create a relation between two entities. Uses `createRelation` under the hood.

## Then
**NOTE:** You need to name the `Thens` a bit differently from the `Givens`, as behat will not distinguish the keywords.
Otherwise, you would create objects/relations instead of checking if they exist.
Usually we do this by adding things like "*exists*" or "*now*".

### `Then there exists a user`
```
| firstname | Test |
| lastname  | User |
```
Check if the entity with the given attributes can be found.
### `Then there exists no user`
The opposite.
### `Then user<userId> is now assigned to group <groupId>`
Check if the relation exists.

