# WordPress Football Sync Plugin

## Scopo

Questa guida descrive installazione e verifica del plugin WordPress
`Football Sync API`, il componente sorgente della sincronizzazione AnWP.

Il codice distribuibile si trova in:

```text
integrations/wordpress/football-sync-api
```

## Installazione

1. Copiare la cartella in `wp-content/plugins/football-sync-api` sul sito.
2. Attivare il plugin dal pannello WordPress.
3. Aprire `Impostazioni > Football Sync API`.
4. Configurare una chiave di almeno 32 caratteri.
5. Verificare `/wp-json/football-sync/v1/info`.

Aggiornamento a `0.2.1`:

1. sostituire i file della cartella plugin
2. aprire una pagina WordPress o richiamare `/info`
3. l'upgrade automatico crea le tabelle tramite `dbDelta`
4. non e necessario disattivare e riattivare il plugin

Per produzione configurare preferibilmente la chiave in `wp-config.php`:

```php
define('FOOTBALL_SYNC_API_KEY', 'chiave-casuale-di-almeno-32-caratteri');
```

## Verifica Endpoint

```bash
curl -H "X-API-Key: CHIAVE" \
  "https://sito.example/wp-json/football-sync/v1/info"
```

La risposta deve indicare:

- `database = OK`
- tabelle `competitions`, `clubs`, `matches`, `post_entities` disponibili
- versione WordPress
- versione plugin `0.2.1`
- `incremental_sync = true`
- `sync_index_ready = true`

Verifica paginazione:

```bash
curl -H "X-API-Key: CHIAVE" \
  "https://sito.example/wp-json/football-sync/v1/competitions?page=1&limit=10"
```

## Postman

Importare la collection:

```text
integrations/wordpress/football-sync-api/postman/football-sync-api.postman_collection.json
```

Configurare nelle variabili della collection:

- `baseUrl`, ad esempio `https://sito.example/wp-json/football-sync/v1`
- `apiKey`, uguale a `FOOTBALL_SYNC_API_KEY`

L'autenticazione `X-API-Key` e configurata a livello di collection e viene
ereditata da tutte le richieste. I filtri opzionali sono disattivati per
default e possono essere abilitati dalla scheda `Params`.

## Prima Baseline Sync

```bash
curl -H "X-API-Key: CHIAVE" \
  "https://sito.example/wp-json/football-sync/v1/sync?updated_after=1970-01-01T00:00:00Z&refresh=true"
```

Controllare che:

- `baseline_created = true` alla prima esecuzione
- `indexed_at` sia valorizzato
- `summary` contenga tutte le sei entita
- gli ID correnti compaiano in `changes.*.updated`

Conservare `indexed_at` come cursore. Alla chiamata successiva usarlo in
`updated_after`; il confronto e esclusivo e restituisce solo variazioni
successive.

La richiesta Postman `Sync incrementale` aggiorna automaticamente la variabile
di collection `updatedAfter` con l'ultimo `indexed_at` ricevuto.

## Diagnostica

`MISSING_TABLES` su `/info`:

- verificare che AnWP Football Leagues sia installato
- verificare il prefisso WordPress
- confrontare le tabelle con il dump analizzato

`ANWP_TABLE_MISSING`:

- l'endpoint richiesto non puo leggere una tabella necessaria
- verificare che la migrazione interna AnWP sia completa

`USE_SYNC_ENDPOINT`:

- `updated_after` e stato passato a un endpoint lista
- richiamare `/sync` e poi recuperare ogni record tramite il filtro `id`

`SYNC_ALREADY_RUNNING`:

- un altro refresh completo e in corso
- attendere il completamento e riprovare

`SYNC_INDEX_ERROR`:

- verificare che `sync_index_ready` sia `true`
- controllare permessi DB WordPress e log PHP

`updated_at = null`:

- il record AnWP non ha un collegamento rilevabile a `wp_posts`
- usare `hash` per confronti manuali fino al sync incrementale

## Tabelle Plugin

- `<prefix>football_sync_index`: hash, cursori e tombstone cancellazioni
- `<prefix>football_sync_logs`: endpoint, esito, durata e client hash

Il plugin non registra API key, payload completi, query string o IP in chiaro.
La pulizia dei log viene eseguita automaticamente una volta al giorno.

## Sicurezza Operativa

- usare HTTPS
- non registrare la chiave completa nei log
- ruotare la chiave separatamente per sviluppo, test e produzione
- non esporre endpoint senza header `X-API-Key`
- valutare allowlist IP quando il server di `arbitri-app` ha IP stabile

## Stato Roadmap

Fase 2 completata e validata in produzione.

Fase 3 completata nel repository:

- endpoint `/sync`
- indice incrementale e cancellazioni
- cache Transient API
- logging e retention
- impostazioni TTL e conservazione log

La versione `0.2.1` deve essere pubblicata prima di ripetere l'import completo.

## Importer Arbitri App

La Fase 4 e implementata in `arbitri-app`.

1. Pubblicare e verificare il plugin WordPress `0.2.1`.
2. Eseguire `database/migrations/2026_08_26_add_wordpress_sync_import.sql`.
3. Eseguire `database/migrations/2026_08_26_add_wordpress_sync_overrides.sql`.
4. Eseguire `database/migrations/2026_09_03_add_wordpress_sync_progress.sql`.
5. Eseguire `database/migrations/2026_09_03_separate_wordpress_connection_status.sql`.
6. Se una sincronizzazione precedente ha creato la stagione duplicata
   `2025-2026`, eseguire
   `database/migrations/2026_09_03_merge_wordpress_current_season.sql`.
7. In produzione impostare `FOOTBALL_SYNC_ENCRYPTION_KEY` nell'ambiente PHP.
8. Aprire `Configurazione > Sincronizzazione` e verificare la connessione.
9. Avviare un `Sync completo` per rilevare le ambiguita residue.
10. Configurare disciplina/stagione nella griglia `Correzioni mapping`; marcare
   `Ignora` solo i record remoti che non devono entrare nell'applicazione.
11. Avviare un secondo `Sync completo` e verificare che gli errori siano zero.
12. Usare il sync incrementale solo dopo il completamento positivo.

Durante il sync la pagina mostra l'avanzamento reale. In caso di refresh, la
barra viene ripristinata tramite heartbeat e stato salvati in `sync_runs`.

La chiave di cifratura deve restare stabile. Cambiarla senza prima ruotare la
chiave API salvata rende il valore esistente non decifrabile.
