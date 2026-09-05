# WordPress Sync Import API

## Scopo

Gli endpoint locali configurano e avviano l'importazione da `Football Sync API`
verso `arbitri-app`.

Tutte le route richiedono JWT e permessi RBAC.

## Endpoint

### `GET /football-sync/source`

Permesso: `import.view`.

Restituisce configurazione e stato della sorgente. La chiave API non viene mai
restituita; `api_key_configured` indica solo se e presente.

Stato connessione:

- `connection_status`: `never`, `success` o `failed`
- `last_connection_at`: data dell'ultima verifica
- `last_connection_message`: dettaglio dell'ultima verifica

Questi campi sono indipendenti dall'esito delle sincronizzazioni.

Campi temporali principali:

- `last_execution_at`: conclusione dell'ultima esecuzione, anche parziale o
  fallita, ricavata da `sync_runs`
- `last_sync_cursor`: checkpoint remoto dell'ultimo sync completato senza
  errori; non avanza in caso di esito parziale per consentire il recupero dei
  record falliti

### `PUT /football-sync/source`

Permesso: `import.manage`.

Payload:

```json
{
  "name": "WordPress produzione",
  "base_url": "https://www.example.it",
  "api_key": "nuova-chiave-opzionale",
  "enabled": true,
  "verify_ssl": true,
  "request_timeout": 20
}
```

Una chiave vuota mantiene quella gia configurata.

### `POST /football-sync/test`

Permesso: `import.manage`.

Interroga `/info` e verifica:

- autenticazione
- stato database remoto `OK`
- supporto sync incrementale

Il test aggiorna soltanto lo stato della connessione. Non modifica
`last_status`, `last_message`, `last_execution_at` o il cursore dell'ultima
sincronizzazione.

### `POST /football-sync/run`

Permesso: `import.run`.

Payload:

```json
{"mode": "incremental"}
```

Modalita:

- `incremental`: usa l'ultimo cursore completato
- `full`: riparte da `1970-01-01T00:00:00Z`

Il cursore avanza soltanto se non esistono record falliti.

### `GET /football-sync/runs`

Permesso: `import.view`.

Restituisce lo storico con conteggi per esito.

### `GET /football-sync/progress`

Permesso: `import.view`.

Restituisce l'esecuzione attiva della sorgente WordPress oppure `run: null`.

```json
{
  "run": {
    "id": 18,
    "sync_mode": "full",
    "status": "running",
    "total_count": 5302,
    "processed_count": 3650,
    "current_entity": "matches",
    "elapsed_seconds": 102,
    "percentage": 68.8
  }
}
```

Finche il plugin sta preparando l'indice remoto, `total_count` e
`processed_count` sono zero e `percentage` e `null`.

### `GET /football-sync/run-items?run_id={id}`

Permesso: `import.view`.

Restituisce il dettaglio per entita e ID esterno.

### `GET /football-sync/overrides`

Permesso: `import.view`.

Restituisce anomalie/correzioni persistenti e l'elenco delle stagioni locali
selezionabili.

### `PUT /football-sync/overrides`

Permesso: `import.manage`.

Payload:

```json
{
  "items": [{
    "entity_type": "competitions",
    "external_id": "19330",
    "football_type": "11",
    "season_local_id": 5,
    "ignored": false
  }]
}
```

Le entita configurabili sono `seasons` e `competitions`. Le correzioni
influenzano solo l'import locale.

## Esiti Item

- `created`
- `updated`
- `skipped`
- `ignored`
- `failed`
- `deleted_at_source`

`deleted_at_source` e una tombstone: il dato locale viene conservato.
`ignored` indica un'esclusione configurata o una dipendenza da una
competizione esclusa e non rende parziale l'esecuzione.
