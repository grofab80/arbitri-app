# Auth API

## POST /login

Effettua login e restituisce token JWT.

### Payload

```json
{
  "username": "admin",
  "password": "password"
}
```

### Token

Il JWT contiene:

- `uid`
- `profile_code`
- `profile_name`
- `first_name`
- `last_name`
- `email`
- `permissions`

`permissions` e un bootstrap informativo. La fonte aggiornata resta
`GET /me`.

## GET /me

Restituisce identita dell'utente autenticato e permessi letti dal database.

### Autorizzazione

Richiede JWT valido.

### Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "admin",
    "profile_id": 1,
    "profile_code": "admin",
    "profile_name": "Amministratore",
    "first_name": "Mario",
    "last_name": "Rossi",
    "email": "mario@example.com",
    "permissions": ["dashboard.view"]
  }
}
```
