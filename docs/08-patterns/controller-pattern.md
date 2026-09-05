# Controller Pattern

Un controller deve essere sottile.

## Deve

- leggere input
- chiamare validator
- chiamare model/service
- restituire response

## Non Deve

- contenere SQL
- contenere business logic complessa
