# Permissions API

## GET /permissions

Restituisce profili, catalogo permessi e assegnazioni correnti.

### Autorizzazione

Richiede:

- `permissions.view`

### Response

```json
{
  "success": true,
  "data": {
    "profiles": [],
    "permissions": [],
    "assignments": []
  }
}
```

## PUT /profile-permissions

Aggiorna i permessi assegnati a un profilo.

### Query

- `profile_id`

### Payload

```json
{
  "permission_ids": [1, 2, 3]
}
```

### Autorizzazione

Richiede:

- `permissions.manage`

### Regole

- Il profilo `admin` non e modificabile da interfaccia/API.
- `admin` deve avere sempre tutti i permessi attivi.
- I permessi inviati devono esistere nel catalogo.
- I permessi con codice `*.delete` possono appartenere solo al profilo `admin`.
- L'API rifiuta l'assegnazione di un permesso `*.delete` a profili non admin.
