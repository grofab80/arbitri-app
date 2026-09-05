# Notifications API

## GET /notifications

Restituisce le notifiche operative visibili all'utente autenticato.

La chiamata sincronizza anche gli alert operativi correnti:

- al primo passaggio crea la baseline degli snapshot
- dai passaggi successivi genera notifiche solo per incrementi reali

### Auth

Richiede JWT e permesso `auth.me`.

### Response

```json
{
  "success": true,
  "data": {
    "unread_count": 1,
    "notifications": [
      {
        "id": 1,
        "alert_code": "matches_without_designation",
        "title": "Partite programmate senza arbitro: nuovo elemento",
        "message": "E stato rilevato 1 nuovo elemento per partite programmate senza arbitro. Totale attuale: 3.",
        "delta": 1,
        "count_value": 3,
        "href": "matches?alert=without_referee",
        "required_permissions": ["designations.edit", "matches.edit"],
        "created_at": "2026-07-22 10:00:00",
        "read_at": null,
        "dismissed_at": null,
        "is_read": false
      }
    ]
  }
}
```

## PUT /notifications-read

Marca come letta una singola notifica visibile all'utente.

### Auth

Richiede JWT e permesso `auth.me`.

### Request

```json
{
  "notification_id": 1
}
```

### Response

```json
{
  "success": true,
  "data": {
    "updated": true
  }
}
```

## PUT /notifications-read-all

Marca come lette tutte le notifiche visibili all'utente.

### Auth

Richiede JWT e permesso `auth.me`.

### Response

```json
{
  "success": true,
  "data": {
    "updated": 3
  }
}
```

## Regole Di Visibilita

- `admin` vede tutte le notifiche operative.
- Gli altri profili vedono solo notifiche compatibili con almeno uno dei
  permessi dichiarati in `required_permissions`.
- Il filtro e backend: la topbar e solo una rappresentazione UI.
