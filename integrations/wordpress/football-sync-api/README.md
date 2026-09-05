# Football Sync API

Plugin WordPress che espone i dati di AnWP Football Leagues tramite endpoint
REST normalizzati per `arbitri-app`.

## Requisiti

- WordPress 6.0 o successivo
- PHP 7.4 o successivo
- AnWP Football Leagues con le tabelle `anwpfl_competitions`, `anwpfl_clubs`
  e `anwpfl_matches`

Il prefisso delle tabelle viene letto da `$wpdb->prefix` e non deve essere
necessariamente `wp_`.

## Installazione

1. Copiare la cartella `football-sync-api` in `wp-content/plugins/`.
2. Attivare `Football Sync API` dal pannello WordPress.
3. Aprire `Impostazioni > Football Sync API`.
4. Conservare la chiave generata o impostarne una di almeno 32 caratteri.
5. Eseguire una richiesta a `/wp-json/football-sync/v1/info` con l'header
   `X-API-Key`.

In produzione e consigliato definire la chiave in `wp-config.php`:

```php
define('FOOTBALL_SYNC_API_KEY', 'una-chiave-casuale-di-almeno-32-caratteri');
```

Quando la costante e presente, prevale sul valore salvato nel database.

## Endpoint MVP

- `GET /wp-json/football-sync/v1/info`
- `GET /wp-json/football-sync/v1/seasons`
- `GET /wp-json/football-sync/v1/competitions`
- `GET /wp-json/football-sync/v1/teams`
- `GET /wp-json/football-sync/v1/stadiums`
- `GET /wp-json/football-sync/v1/referees`
- `GET /wp-json/football-sync/v1/matches`
- `GET /wp-json/football-sync/v1/sync`

Esempio:

```bash
curl -H "X-API-Key: CHIAVE" \
  "https://example.test/wp-json/football-sync/v1/matches?page=1&limit=100"
```

## Collection Postman

La collection pronta per l'import si trova in:

```text
postman/football-sync-api.postman_collection.json
```

Dopo l'import impostare le variabili di collection:

- `baseUrl`: URL completo fino a `/wp-json/football-sync/v1`, senza slash finale
- `apiKey`: valore configurato nel plugin o in `FOOTBALL_SYNC_API_KEY`

Gli ID e le date di esempio sono ulteriori variabili modificabili. I filtri
opzionali sono presenti nelle richieste ma disattivati di default.

## Sync Incrementale

Prima sincronizzazione e creazione baseline:

```bash
curl -H "X-API-Key: CHIAVE" \
  "https://example.test/wp-json/football-sync/v1/sync?updated_after=1970-01-01T00:00:00Z&refresh=true"
```

La risposta restituisce gli ID `updated` e `deleted` per ogni entita. Salvare
`indexed_at` e usarlo come `updated_after` nella richiesta successiva.

`updated_after` e un cursore esclusivo: vengono restituiti solo i cambiamenti
osservati dopo quel valore. Il refresh e attivo per default; con
`refresh=false` viene letto l'indice esistente senza interrogare nuovamente
tutte le tabelle AnWP.

Per ogni ID aggiornato richiamare il relativo endpoint con il filtro `id`.
Le cancellazioni sono tombstone: il plugin non elimina dati in sistemi esterni.

## Cache E Log

- le risposte lista usano la WordPress Transient API
- TTL predefinito: 300 secondi, configurabile da 60 a 3600
- `refresh=true` sugli endpoint lista forza il ricalcolo della risposta
- il refresh dell'indice invalida la generazione corrente della cache
- ogni chiamata REST viene registrata in `football_sync_logs`
- gli IP sono memorizzati solo come hash HMAC
- la chiave API e i parametri della richiesta non vengono registrati
- conservazione log predefinita: 30 giorni, configurabile da 1 a 365

## Limiti Correnti

- `updated_at` puo essere `null` quando AnWP non collega il record a
  `wp_posts.post_modified`; `hash` resta comunque stabile.
- stagioni, stadi e arbitri sono ricostruiti perche il database analizzato non
  contiene tabelle AnWP dedicate.
- il mapping di `game_status` e `special_status` e conservativo e deve essere
  verificato sui dati reali del sito.
- `football_type` viene dedotto da nome competizione e lega; se non rilevabile
  viene restituito `null`.
- sono riconosciute anche le forme reali `Calcio 5`, `Calcio 7`, `Calcio 11`
  e `CoppAca5`; i nomi privi di disciplina restano volutamente da configurare
  nell'importer.
- ogni competizione espone `league_external_id` e `league_name` per supportare
  le correzioni manuali lato `arbitri-app`.
- `updated_after` sugli endpoint lista non filtra direttamente i record: usare
  `/sync` e poi il filtro `id` sull'endpoint dell'entita.

## Filtri WordPress

Il mapping puo essere adattato senza modificare il plugin:

- `football_sync_api_key`
- `football_sync_api_competition_type`
- `football_sync_api_football_type`
- `football_sync_api_stadium_record`
- `football_sync_api_referee_record`
- `football_sync_api_match_record`

Gli errori interni attivano l'action `football_sync_api_error`.

## Sicurezza

- usare esclusivamente HTTPS in produzione
- non inserire la chiave API in query string
- usare una chiave diversa per ogni ambiente
- ruotare la chiave in caso di esposizione
- limitare a livello web server l'accesso agli endpoint, se possibile
