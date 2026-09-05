# Balance API

## Endpoint

- `GET /balance`
- `GET /balance-season-comparison`
- `GET /balance-season-analysis`
- `PUT /balance-closure-approve`
- `PUT /balance-closure-archive`
- `GET /balance-export`

## Autorizzazione

Richiede:

- `balance.view`

L'export richiede:

- `balance.export`

L'approvazione snapshot richiede:

- `balance.approve`

La storicizzazione dello snapshot approvato richiede:

- `balance.archive`

## Regole

- usa la stagione corrente
- deduce entrate e uscite dai movimenti della stagione corrente
- usa la gerarchia categorie per distinguere `Entrate` e `Uscite`
- supporta filtro periodo dentro i limiti della stagione corrente
- supporta filtro per competizione
- supporta filtro per squadra
- supporta filtro per arbitro
- supporta confronto tra stagioni
- il bilancio operativo della stagione corrente resta derivato dai movimenti
- le stagioni chiuse possono avere uno snapshot in `balance_closures`
- lo snapshot di chiusura nasce in stato `draft`; approvazione e storicizzazione
  saranno gestite da fasi successive
- lo snapshot e versionato e contiene riepilogo, categorie, competizioni e
  andamento mensile
- una chiusura gia `approved` non viene sovrascritta dalle rigenerazioni
  automatiche

## Query

- `from`: data inizio opzionale, formato `YYYY-MM-DD`
- `to`: data fine opzionale, formato `YYYY-MM-DD`
- `competition_id`: competizione opzionale
- `team_id`: squadra opzionale
- `referee_id`: arbitro opzionale

Le date fuori stagione vengono ricondotte ai limiti della stagione corrente.

## Response

```json
{
  "season": {
    "id": 1,
    "name": "2025/2026",
    "starts_on": "2025-09-01",
    "ends_on": "2026-06-30"
  },
  "filters": {
    "from": "2025-09-01",
    "to": "2026-06-30",
    "competition_id": 1,
    "team_id": 2,
    "referee_id": 3
  },
  "filter_options": {
    "competitions": [
      {
        "id": 1,
        "name": "Campionato"
      }
    ],
    "teams": [
      {
        "id": 2,
        "name": "Real Sporting"
      }
    ],
    "referees": [
      {
        "id": 3,
        "name": "Mario Rossi"
      }
    ]
  },
  "income": 13400,
  "expenses": 10200,
  "profit": 3200,
  "total_movements": 42,
  "categories": [],
  "competitions": [],
  "monthly": {
    "labels": ["09/2025"],
    "income": [1000],
    "expenses": [500],
    "profit": [500]
  }
}
```

## GET /balance-season-comparison

Restituisce il confronto economico tra tutte le stagioni presenti.

La risposta usa lo snapshot approvato quando presente, altrimenti ricalcola dai
movimenti associati alla stagione. Non applica i filtri della stagione corrente.

```json
{
  "rows": [
    {
      "season_id": 1,
      "season_name": "2025/2026",
      "starts_on": "2025-07-01",
      "ends_on": "2026-06-30",
      "status": "in_corso",
      "is_current": true,
      "data_source": "movements",
      "income": 13400,
      "expenses": 10200,
      "profit": 3200,
      "profit_change": null,
      "profit_change_percent": null,
      "total_movements": 42
    }
  ],
  "chart": {
    "labels": ["2025/2026"],
    "income": [13400],
    "expenses": [10200],
    "profit": [3200]
  }
}
```

## GET /balance-season-analysis

Restituisce il confronto dettagliato tra due stagioni.

Per ogni stagione confrontata, la risposta usa lo snapshot approvato quando
presente. Se non esiste uno snapshot approvato, ricalcola dai movimenti.

Query opzionali:

- `base_season_id`
- `compare_season_id`

La risposta non applica i filtri della stagione corrente.

```json
{
  "season_options": [],
  "base_season": {
    "id": 1,
    "name": "2024/2025"
  },
  "compare_season": {
    "id": 2,
    "name": "2025/2026"
  },
  "base_source": "snapshot",
  "compare_source": "movements",
  "summary": {
    "base": {},
    "compare": {},
    "profit_change": 1200,
    "profit_change_percent": 20.5
  },
  "categories": [],
  "competitions": [],
  "chart": {
    "labels": ["2024/2025", "2025/2026"],
    "income": [10000, 12000],
    "expenses": [8000, 8800],
    "profit": [2000, 3200]
  },
  "message": null
}
```

## GET /balance-export

Restituisce un CSV del bilancio della stagione corrente.

Accetta gli stessi filtri `from`, `to`, `competition_id`, `team_id` e
`referee_id` di `GET /balance`.

Contenuto:

- riepilogo entrate/uscite/saldo
- riepilogo per categoria
- riepilogo per competizione

Formato:

- `text/csv`
- separatore `;`
- importi con separatore decimale `,`

## PUT /balance-closure-approve

Approva lo snapshot di chiusura di una stagione chiusa.

Query:

- `season_id`: stagione da approvare

Regole:

- richiede profilo `admin`
- richiede permesso `balance.approve`
- la stagione deve essere in stato `chiuso`
- se lo snapshot non esiste ancora, viene generato prima dell'approvazione
- se lo snapshot e gia `approved`, non viene modificato
- l'approvazione non elimina ancora i movimenti della stagione

## PUT /balance-closure-archive

Elimina i movimenti di una stagione chiusa dopo approvazione dello snapshot.

Query:

- `season_id`: stagione da storicizzare

Regole:

- richiede profilo `admin`
- richiede permesso `balance.archive`
- la stagione deve essere in stato `chiuso`
- lo snapshot deve essere `approved`
- se i movimenti sono gia stati eliminati, l'operazione e idempotente
- registra `movements_deleted_at` e `deleted_movements_count`
