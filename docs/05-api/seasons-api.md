# Seasons API

## Endpoint

- `GET /seasons`
- `GET /current-season`
- `POST /seasons`
- `PUT /seasons-current`
- `PUT /seasons-status`

## Note

La modifica dello stato stagione e riservata ad admin.

## Stati

- `nuovo`
- `in_corso`
- `chiuso`

## PUT /seasons-status

Aggiorna lo stato di una stagione.

### Payload

```json
{
  "status": "in_corso"
}
```

Quando lo stato viene impostato a `in_corso`, il backend garantisce che non ci siano altre stagioni in corso.

Quando una stagione passa a `chiuso`, direttamente o per chiusura automatica
all'avvio di una nuova stagione, il backend genera o aggiorna lo snapshot del
bilancio in `balance_closures`.
