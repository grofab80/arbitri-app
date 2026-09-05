# Users API

## GET /users

Restituisce elenco utenti e profili disponibili.

### Autorizzazione

Richiede:

- `users.view`

## PUT /users-profile

Endpoint compatibile per aggiornare solo il profilo RBAC assegnato a un utente.

Per la gestione completa usare `PUT /users`.

### Query

- `id`

### Payload

```json
{
  "profile_id": 2
}
```

### Autorizzazione

Richiede:

- `users.edit`

## POST /users

Crea un nuovo utente.

### Payload

```json
{
  "username": "mrossi",
  "first_name": "Mario",
  "last_name": "Rossi",
  "email": "mario@example.com",
  "profile_id": 2,
  "password": "test123!"
}
```

### Autorizzazione

Richiede:

- `users.create`

## PUT /users

Modifica un utente esistente.

### Query

- `id`

### Payload

Stesso payload di creazione. In modifica `password` e opzionale: se vuota non
viene cambiata.

### Autorizzazione

Richiede:

- `users.edit`

## DELETE /users

Elimina un utente esistente.

### Query

- `id`

### Autorizzazione

Richiede:

- `users.delete`

### Regole

- non e possibile eliminare il proprio utente
- non e possibile eliminare l'ultimo amministratore

## Note

`first_name` e `last_name` sono campi separati.

`users.role` non e piu usato: la profilazione passa da `profile_id`.
