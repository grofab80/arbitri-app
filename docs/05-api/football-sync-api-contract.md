# Football Sync API Contract

## Scopo

Questo documento definisce il contratto REST che il plugin WordPress
`Football Sync API` dovra esporre ad `arbitri-app`.

Il contratto e intenzionalmente indipendente dai nomi reali delle tabelle AnWP.
Il plugin WordPress si occupa di leggere AnWP e normalizzare i dati.

## Implementazione Fase 2

Il plugin MVP e disponibile in:

```text
integrations/wordpress/football-sync-api
```

Versione corrente: `0.2.1`.

La versione `0.1.0` e stata validata sul sito WordPress reale. La versione
`0.2.0` introduce sync incrementale, cache e logging. `0.2.1` amplia il
riconoscimento delle discipline ed espone il contesto lega.

## Origine Schema Verificata

Il dump struttura WordPress `Sql1354899_2.sql` conferma che le entita principali
AnWP disponibili sono:

- `wp_anwpfl_competitions`
- `wp_anwpfl_clubs`
- `wp_anwpfl_matches`
- `wp_anwpfl_standings`
- tabelle di supporto per giocatori, lineups, formazioni, statistiche e import

Nel dump non sono presenti tabelle dedicate per stagioni, stadi o arbitri.
Il plugin WordPress deve quindi normalizzare:

- stagioni da `season_ids`, `season_text` e `matches.season_id`
- stadi da `stadium_id` e dettagli recuperati da WordPress/AnWP
- arbitri da `matches.referee`, usando un ID esterno deterministico

## Base URL

```text
/wp-json/football-sync/v1
```

## Autenticazione

Header richiesto:

```text
X-API-Key: <api-key>
```

In alternativa futura:

```text
Authorization: Bearer <token>
```

La chiave dell'MVP viene generata all'attivazione e gestita da
`Impostazioni > Football Sync API`. In produzione puo essere definita tramite
la costante `FOOTBALL_SYNC_API_KEY` in `wp-config.php`.

## Formato Response

Ogni endpoint lista deve restituire:

```json
{
  "success": true,
  "count": 1,
  "total": 24,
  "page": 1,
  "limit": 100,
  "has_more": false,
  "last_update": "2026-07-24 10:00:00",
  "data": []
}
```

Ogni errore deve restituire:

```json
{
  "success": false,
  "error": "Messaggio errore",
  "code": "ERROR_CODE"
}
```

## Filtri Comuni

- `id`: ID esterno puntuale
- `page`: pagina, default `1`
- `limit`: elementi per pagina, default `100`, massimo consigliato `500`
- `refresh`: forza il ricalcolo della risposta lista, default `false`

`updated_after` appartiene esclusivamente a `/sync`. Se usato sugli endpoint
lista restituisce HTTP `400` con codice `USE_SYNC_ENDPOINT`.

Formato data/ora consigliato:

```text
YYYY-MM-DD HH:MM:SS
```

## Campi Comuni Record

Ogni record deve includere:

```json
{
  "external_id": 1,
  "updated_at": "2026-07-24 10:00:00",
  "hash": "sha256..."
}
```

Nell'MVP `updated_at` puo essere `null` quando il record AnWP non e collegato
a un post WordPress con `post_modified`. `hash` e sempre valorizzato e non
include `updated_at`, quindi resta stabile tra richieste identiche.

## GET /info

Restituisce stato plugin e compatibilita.

```json
{
  "success": true,
  "data": {
    "plugin": "Football Sync API",
    "version": "0.2.1",
    "wordpress": "6.x",
    "anwp": "0.18.3",
    "database": "OK",
    "timezone": "Europe/Rome",
    "incremental_sync": true,
    "sync_index_ready": true,
    "last_indexed_at": "2026-08-26 10:00:00",
    "index_stats": {
      "active": 315,
      "deleted": 2
    },
    "cache_ttl_seconds": 300,
    "log_retention_days": 30
  }
}
```

## GET /seasons

Filtri:

- `id`
- `page`
- `limit`

Record:

```json
{
  "external_id": 8,
  "name": "2025/2026",
  "starts_on": "2025-07-01",
  "ends_on": "2026-06-30",
  "is_current": true,
  "status": "active",
  "updated_at": "2026-07-24 10:00:00",
  "hash": "sha256..."
}
```

## GET /competitions

Filtri:

- `id`
- `season`
- `page`
- `limit`

Record:

```json
{
  "external_id": 4,
  "season_external_id": 8,
  "season_external_ids": [8],
  "name": "Super League Oro C11",
  "league_external_id": 12,
  "league_name": "Calcio a 11",
  "type": "league",
  "football_type": "11",
  "logo_url": "https://example.test/logo.png",
  "status": "active",
  "updated_at": "2026-07-24 10:00:00",
  "hash": "sha256..."
}
```

Valori `type` ammessi nel contratto:

- `league`
- `tournament`

Valori `football_type` ammessi:

- `11`
- `7`
- `5`

`football_type` puo essere `null` quando nome e lega non rendono esplicita la
disciplina. `league_external_id` e `league_name` forniscono il contesto per una
correzione manuale nell'applicazione destinataria.

## GET /teams

Filtri:

- `id`
- `competition`
- `season`
- `page`
- `limit`

Record:

```json
{
  "external_id": 12,
  "name": "FC Bellavista",
  "short_name": "Bellavista",
  "slug": "fc-bellavista",
  "city": "Ivrea",
  "country": "Italia",
  "logo_url": "https://example.test/logo.png",
  "primary_stadium_external_id": 5,
  "competition_external_ids": [4, 7],
  "website": "https://example.test",
  "facebook": null,
  "instagram": null,
  "updated_at": "2026-07-24 10:00:00",
  "hash": "sha256..."
}
```

## GET /stadiums

Filtri:

- `id`
- `page`
- `limit`

Record:

```json
{
  "external_id": 5,
  "name": "Stadio Comunale",
  "address": "Via Roma 1",
  "city": "Ivrea",
  "province": "TO",
  "postal_code": "10015",
  "country": "Italia",
  "capacity": 500,
  "latitude": 45.4670000,
  "longitude": 7.8760000,
  "photo_url": "https://example.test/stadio.jpg",
  "can_host_11": true,
  "can_host_7": true,
  "can_host_5": false,
  "updated_at": "2026-07-24 10:00:00",
  "hash": "sha256..."
}
```

## GET /referees

Filtri:

- `id`
- `page`
- `limit`

Record:

```json
{
  "external_id": 15,
  "name": "Mario Rossi",
  "first_name": "Mario",
  "last_name": "Rossi",
  "city": "Ivrea",
  "country": "Italia",
  "can_referee_11": true,
  "can_referee_7": true,
  "can_referee_5": true,
  "updated_at": "2026-07-24 10:00:00",
  "hash": "sha256..."
}
```

Nota: se AnWP non gestisce arbitri come entita strutturata, il plugin puo
dedurli dalle partite. In quel caso l'endpoint deve indicare `source =
"matches"`.

Nel dump analizzato gli arbitri sono effettivamente presenti come testo libero
in `wp_anwpfl_matches.referee`.

## GET /matches

Filtri:

- `id`
- `competition`
- `season`
- `team`
- `stadium`
- `referee`
- `status`
- `date_from`
- `date_to`
- `page`
- `limit`

Record:

```json
{
  "external_id": 125,
  "season_external_id": 8,
  "competition_external_id": 4,
  "match_day": 12,
  "difficulty_rating": null,
  "match_date": "2026-10-11",
  "match_time": "15:30",
  "status": "played",
  "home_team_external_id": 18,
  "away_team_external_id": 22,
  "home_goals": 3,
  "away_goals": 1,
  "result_type": "played",
  "walkover_reason": null,
  "field_external_id": 6,
  "referee_external_id": 15,
  "referee_name": "Mario Rossi",
  "attendance": 512,
  "youtube_url": "https://youtube.com/...",
  "highlights_url": null,
  "notes": null,
  "updated_at": "2026-07-24 10:00:00",
  "hash": "sha256..."
}
```

Valori `status` ammessi nel contratto:

- `scheduled`
- `played`
- `cancelled`

Valori `result_type` ammessi:

- `played`
- `walkover_home`
- `walkover_away`

Origine AnWP consigliata:

- `match_id` -> `external_id`
- `season_id` -> `season_external_id`
- `competition_id` -> `competition_external_id`
- `match_week` -> `match_day`
- `priority` -> `difficulty_rating`, se coerente con il dominio operativo
- `home_club` / `away_club` -> squadre
- `stadium_id` -> stadio
- `referee` -> arbitro dedotto
- `kickoff` -> `match_date` + `match_time`
- `finished`, `game_status`, `special_status` -> `status` / `result_type`

## GET /sync

Restituisce solo gli ID esterni modificati o cancellati dopo un timestamp.

Filtri:

- `updated_after`: cursore obbligatorio ed esclusivo
- `refresh`: aggiorna l'indice dalla sorgente AnWP, default `true`

Response:

```json
{
  "success": true,
  "generated_at": "2026-07-24 10:00:00",
  "indexed_at": "2026-07-24 10:00:00",
  "baseline_created": false,
  "refreshed": true,
  "summary": {
    "seasons": {"indexed": 2, "deleted": 0},
    "competitions": {"indexed": 9, "deleted": 0},
    "teams": {"indexed": 28, "deleted": 0},
    "stadiums": {"indexed": 12, "deleted": 0},
    "referees": {"indexed": 18, "deleted": 0},
    "matches": {"indexed": 246, "deleted": 1}
  },
  "changes": {
    "seasons": {
      "updated": [8],
      "deleted": []
    },
    "competitions": {
      "updated": [4],
      "deleted": []
    },
    "teams": {
      "updated": [12, 18],
      "deleted": [35]
    },
    "stadiums": {
      "updated": [5],
      "deleted": []
    },
    "referees": {
      "updated": [15],
      "deleted": []
    },
    "matches": {
      "updated": [125],
      "deleted": []
    }
  }
}
```

Prima chiamata:

```text
updated_after=1970-01-01T00:00:00Z&refresh=true
```

Il plugin crea la baseline e restituisce tutti gli ID attivi come `updated`.
Il client deve salvare `indexed_at` e usarlo come `updated_after` nella
richiesta successiva. Il confronto e esclusivo (`changed_at > updated_after`),
quindi il ciclo precedente non viene riproposto.

Con `refresh=false` il plugin interroga solamente l'indice esistente. Le
cancellazioni sono restituite come tombstone e non causano eliminazioni in
`arbitri-app` senza una decisione esplicita dell'importer.

## Normalizzazioni Obbligatorie

- date in `YYYY-MM-DD`
- orari in `HH:MM`
- boolean come `true`/`false`
- ID sempre numerici
- stringhe vuote normalizzate a `null`
- URL assoluti
- importi non previsti in questo contratto

## Comportamento Fase 3

- `/sync` mantiene un indice persistente di hash e cancellazioni
- ogni refresh completo e transazionale e protetto da lock
- le risposte lista sono memorizzate tramite Transient API
- il TTL cache e configurabile tra 60 e 3600 secondi
- le chiamate REST sono registrate senza API key o IP in chiaro
- i log vengono eliminati automaticamente secondo la retention configurata
- `updated_at` puo essere `null`
- il mapping di `game_status` e `special_status` e conservativo
- `football_type` puo essere `null` se nome e lega non indicano la disciplina
- stadi e arbitri possono avere dati parziali perche sono entita dedotte

I mapping possono essere adattati tramite filtri WordPress documentati nel
README del plugin, senza modificare il contratto consumato da `arbitri-app`.
