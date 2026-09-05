# WordPress / AnWP Sync Mapping

## Scopo

Questo documento definisce il mapping tra i dati esposti dal futuro plugin
WordPress `Football Sync API` e le tabelle locali di `arbitri-app`.

La struttura reale delle tabelle AnWP e stata verificata sul dump struttura
WordPress `Sql1354899_2.sql`. Il mapping locale resta stabile e viene
alimentato dal contratto REST del plugin `Football Sync API`.

Il provider dati della Fase 2 e implementato in
`integrations/wordpress/football-sync-api/includes/class-football-sync-repository.php`.
Usa sempre `$wpdb->prefix`, query preparate per i filtri e non assume che il
prefisso sia `wp_`.

## Verifica Schema Reale AnWP

Il dump struttura conferma il prefisso `wp_`.

Sul database WordPress reale, quando disponibile anche con dati, eseguire queste
query per verificare post type, meta e valori effettivi.

### Tabelle AnWP Disponibili

```sql
SELECT table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name LIKE '%anwp%'
ORDER BY table_name;
```

### Colonne Tabelle AnWP

```sql
SELECT
    table_name,
    ordinal_position,
    column_name,
    column_type,
    is_nullable,
    column_key,
    column_default
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name LIKE '%anwp%'
ORDER BY table_name, ordinal_position;
```

### Indici Tabelle AnWP

```sql
SELECT
    table_name,
    index_name,
    seq_in_index,
    column_name,
    non_unique
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name LIKE '%anwp%'
ORDER BY table_name, index_name, seq_in_index;
```

### Tabelle Post Meta Ancora Usate

Alcuni dati potrebbero restare su `wp_posts` / `wp_postmeta`.

```sql
SELECT post_type, COUNT(*) AS total
FROM wp_posts
WHERE post_type LIKE '%anwp%'
GROUP BY post_type
ORDER BY post_type;
```

```sql
SELECT pm.meta_key, COUNT(*) AS total
FROM wp_postmeta pm
JOIN wp_posts p ON p.ID = pm.post_id
WHERE p.post_type LIKE '%anwp%'
GROUP BY pm.meta_key
ORDER BY total DESC, pm.meta_key
LIMIT 200;
```

## Tabelle AnWP Reali Nel Dump

Tabelle dedicate AnWP presenti:

- `wp_anwpfl_adv_buttons`
- `wp_anwpfl_club_history`
- `wp_anwpfl_matches`
- `wp_anwpfl_clubs`
- `wp_anwpfl_competitions`
- `wp_anwpfl_formations`
- `wp_anwpfl_import_mapping`
- `wp_anwpfl_layouts`
- `wp_anwpfl_lineups`
- `wp_anwpfl_missing_players`
- `wp_anwpfl_odds`
- `wp_anwpfl_player_data`
- `wp_anwpfl_players`
- `wp_anwpfl_players_manual_stats`
- `wp_anwpfl_post_entities`
- `wp_anwpfl_predictions`
- `wp_anwpfl_standings`
- `wp_anwpfl_transfers`

Tabelle attese ma non presenti come tabelle dedicate:

- `wp_anwpfl_seasons`
- `wp_anwpfl_stadiums`
- `wp_anwpfl_referees`

Queste entita devono quindi essere lette o ricostruite da campi AnWP esistenti,
da `wp_posts` / `wp_postmeta`, oppure da classi/metodi interni AnWP.

## Colonne Reali Utili

### `wp_anwpfl_competitions`

| Colonna | Uso Sync |
| --- | --- |
| `competition_id` | ID esterno competizione |
| `title` | nome competizione |
| `post_name` | slug |
| `league_id`, `league_text` | lega/origine disciplina, se valorizzata |
| `season_ids` | ID stagioni associate |
| `season_text` | nome/testo stagione |
| `type` | tipo competizione AnWP |
| `format_robin`, `format_knockout` | supporto mapping campionato/torneo |
| `multistage`, `multistage_main`, `stage_title`, `stage_order` | competizioni multistage |
| `matchweek_current` | giornata corrente |
| `logo`, `logo_big` | media non importato in Fase 1 |

### `wp_anwpfl_clubs`

| Colonna | Uso Sync |
| --- | --- |
| `club_id` | ID esterno squadra |
| `title` | nome squadra |
| `post_name` | slug |
| `abbr` | nome breve |
| `city` | citta |
| `nationality` | nazione |
| `stadium_id` | stadio principale |
| `club_external_id` | ID esterno aggiuntivo, se valorizzato |
| `logo`, `logo_big` | media non importato in Fase 1 |
| `club_details`, `club_social`, `club_custom` | dati estesi futuri |
| `squad_seasons`, `squad_staff_seasons` | storico rosa/staff, fuori Fase 1 |

### `wp_anwpfl_matches`

| Colonna | Uso Sync |
| --- | --- |
| `match_id` | ID esterno partita |
| `competition_id` | competizione |
| `main_stage_id`, `group_id` | fase/gruppo |
| `season_id` | stagione |
| `league_id` | lega/origine disciplina, se valorizzata |
| `home_club`, `away_club` | squadre |
| `kickoff` | data e ora locale |
| `kickoff_gmt` | data e ora GMT |
| `finished` | partita giocata |
| `game_status` | stato AnWP |
| `stadium_id` | stadio |
| `match_week` | giornata |
| `priority` | possibile input per difficolta partita |
| `home_goals`, `away_goals` | risultato |
| `special_status` | possibile annullata/tavolino, da verificare sui valori |
| `referee` | arbitro come testo |
| `attendance` | pubblico, non importato in Fase 1 |
| `extra_info` | dati extra futuri |

### `wp_anwpfl_standings`

| Colonna | Uso Sync |
| --- | --- |
| `standing_id` | ID esterno classifica |
| `competition_id` | competizione |
| `group_id` | gruppo |
| `points_win`, `points_draw`, `points_loss` | regole punteggio |
| `ranking_rules` | regole ordinamento |
| `table_main` | classifica principale serializzata |
| `table_main_home`, `table_main_away` | classifiche casa/trasferta |
| `manual_filling` | classifica manuale |
| `last_recalc` | ultimo ricalcolo |

## Osservazioni Sullo Schema Reale

- Le tabelle AnWP dedicate non dichiarano foreign key fisiche.
- Non sono presenti colonne `created_at` / `updated_at` sulle tabelle AnWP
  principali; il plugin WordPress dovra derivare `updated_at` da
  `wp_posts.post_modified`, da meta disponibili, o produrre un hash record.
- Le relazioni squadra/competizione non sono esplicite in una tabella dedicata.
  Per il sync possono essere derivate dalle partite, dalle competizioni e da
  eventuali strutture serializzate AnWP.
- Gli arbitri sono testo libero in `wp_anwpfl_matches.referee`; in Fase 2 si
  genera un ID numerico deterministico CRC32 sul nome normalizzato. La Fase 4
  dovra comunque mantenere il mapping esterno per gestire eventuali omonimie.
- Gli stadi sono referenziati come ID, ma il dettaglio anagrafico va recuperato
  via WordPress/AnWP.
- Il plugin MVP usa `wp_anwpfl_post_entities` e `wp_posts.post_modified` quando
  il collegamento esiste; in caso contrario espone `updated_at = null` e un
  hash stabile del record normalizzato.

## Tabelle Locali Coinvolte

Mapping verso `arbitri-app`:

- `seasons`
- `competitions`
- `teams`
- `competition_teams`
- `fields`
- `referees`
- `matches`
- `competition_standings` in fase successiva

## Tabelle Tecniche Del Plugin WordPress

La versione `0.2.0` crea due tabelle nel database WordPress usando il prefisso
reale del sito.

### `<prefix>football_sync_index`

Indice dello stato osservato per ogni entita esterna.

Campi:

- `entity_type`
- `external_id`
- `external_hash`
- `scan_token`
- `first_seen_at`
- `last_seen_at`
- `changed_at`
- `deleted_at`

Vincoli e indici:

- primary key composta `entity_type, external_id`
- indice su `changed_at`
- indice su `deleted_at`

`deleted_at` rappresenta una tombstone. Un record che ricompare viene riattivato
e riceve un nuovo `changed_at`.

### `<prefix>football_sync_logs`

Log tecnico delle chiamate REST.

Campi:

- `id`
- `request_id`
- `endpoint`
- `method`
- `status_code`
- `duration_ms`
- `response_count`
- `client_hash`
- `created_at`

Non vengono salvati API key, query string, payload completi o IP in chiaro.

## Tabelle Locali Sync

Implementate dalla migrazione
`2026_08_26_add_wordpress_sync_import.sql`.

### `external_sources`

Sorgenti esterne configurabili.

Campi principali:

- `id`
- `code`, ad esempio `wordpress_anwp`
- `name`
- `base_url`
- `api_key_encrypted` (mai restituita dalle API locali)
- `enabled`
- `verify_ssl`
- `request_timeout`
- `connection_status`: `never`, `success` o `failed`
- `last_connection_at`
- `last_connection_message`
- `last_sync_cursor`
- `last_status`: esito dell'ultima sincronizzazione, non della connessione
- `last_sync_at`
- `created_at`
- `updated_at`

Il test `/info` aggiorna esclusivamente i campi `connection_*`. I campi
`last_status`, `last_message` e il cursore descrivono soltanto le esecuzioni di
sincronizzazione.

### `external_mappings`

Mappa ID esterni verso ID locali.

Campi principali:

- `id`
- `source_id`
- `entity_type`
- `external_id`
- `local_id`
- `external_hash`
- `last_seen_at`
- `last_synced_at`
- `deleted_at_source`
- `created_at`
- `updated_at`

Vincolo:

- unique su `source_id`, `entity_type`, `external_id`

### `sync_runs`

Testata di una sincronizzazione.

Campi principali:

- `id`
- `source_id`
- `sync_mode`: `full`, `incremental`
- `trigger_type`: `manual`, `scheduled`
- `started_at`
- `finished_at`
- `status`: `running`, `success`, `partial`, `failed`
- `message`
- `created_by`
- `total_count`, totale record individuati dal plugin
- `processed_count`, record gia elaborati
- `current_entity`, fase attualmente in esecuzione
- `heartbeat_at`, ultimo avanzamento registrato

I campi di avanzamento sono introdotti dalla migrazione
`2026_09_03_add_wordpress_sync_progress.sql`. Un'esecuzione senza heartbeat da
oltre 30 minuti viene considerata interrotta e marcata `failed`.

### `sync_run_items`

Dettaglio esiti per entita.

Campi principali:

- `id`
- `sync_run_id`
- `entity_type`
- `external_id`
- `local_id`
- `action`: `created`, `updated`, `skipped`, `failed`, `deleted_at_source`
- `message`

Le tombstone valorizzano `external_mappings.deleted_at_source` e non eliminano
il record locale.

## Mapping Entita

| Football Sync API | Arbitri-app | Note |
| --- | --- | --- |
| `seasons` | `seasons` | crea o aggiorna stagioni |
| `competitions` | `competitions` | richiede stagione gia mappata |
| `teams` | `teams` + `competition_teams` | una squadra puo stare in piu competizioni |
| `stadiums` | `fields` | in UI sono chiamati stadi |
| `referees` | `referees` | opzionale se AnWP non li struttura |
| `matches` | `matches` | richiede competizione, squadre, stadio/arbitro se presenti |
| `standings` | `competition_standings` | fase successiva |

## Mapping Campi

### Seasons

| API | Locale | Regola |
| --- | --- | --- |
| `external_id` | `external_mappings.external_id` | obbligatorio |
| `name` | `seasons.name` | obbligatorio |
| `starts_on` | `seasons.starts_on` | fallback da nome stagione se assente |
| `ends_on` | `seasons.ends_on` | fallback da nome stagione se assente |
| `is_current` | `seasons.is_current` | una sola stagione corrente |
| `status` | `seasons.status` | mapping verso `nuovo`, `in_corso`, `chiuso` |

I nomi nel formato `YYYY-YYYY` ricevuti da WordPress vengono normalizzati nel
formato canonico locale `YYYY/YYYY`. In questo modo `2025-2026` e `2025/2026`
identificano la stessa stagione e non generano duplicati.
| `hash` | `external_mappings.external_hash` | evita update inutili |

Origine AnWP:

- `wp_anwpfl_competitions.season_ids`
- `wp_anwpfl_competitions.season_text`
- `wp_anwpfl_matches.season_id`
- eventuali post/meta AnWP se disponibili

### Competitions

| API | Locale | Regola |
| --- | --- | --- |
| `external_id` | `external_mappings.external_id` | obbligatorio |
| `season_external_id` | `competitions.season_id` | via mapping stagione |
| `name` | `competitions.name` | obbligatorio |
| `league_external_id`, `league_name` | `external_sync_overrides.source_context_json` | contesto diagnostico |
| `type` | `competitions.type` | `league` -> `campionato`, `tournament` -> `torneo` |
| `football_type` | `competitions.football_type` | `11`, `7`, `5` |
| `season_external_id` | `competitions.season` | salvare anche nome stagione legacy |
| `hash` | `external_mappings.external_hash` | evita update inutili |

Origine AnWP:

- `external_id`: `wp_anwpfl_competitions.competition_id`
- `name`: `wp_anwpfl_competitions.title`
- `type`: da `type`, `format_robin`, `format_knockout`
- `season_external_id`: da `season_ids`, scegliendo la stagione singola o
  generando record separati se AnWP associa piu stagioni
- `football_type`: non e presente una colonna esplicita; va configurato o
  dedotto da nome/lega/regola manuale

## Correzioni Import

La tabella `external_sync_overrides` conserva esclusivamente regole locali:

- disciplina manuale per una competizione ambigua
- stagione locale associata a una stagione o competizione esterna
- esclusione esplicita di record anomali
- contesto sorgente e ultimo errore utile alla diagnosi

Le correzioni non vengono inviate a WordPress. Una competizione ignorata rende
`ignored`, e non `failed`, anche le relative squadre prive di altre
competizioni e le relative partite. `sync_runs.ignored_count` separa questi
record dagli errori effettivi.

### Teams

| API | Locale | Regola |
| --- | --- | --- |
| `external_id` | `external_mappings.external_id` | obbligatorio |
| `name` | `teams.name` | obbligatorio |
| `primary_stadium_external_id` | `teams.field_id` | via mapping stadio, opzionale |
| `competition_external_ids[]` | `competition_teams` | crea relazioni molti-a-molti |
| primo elemento competizione | `teams.competition_id` | solo legacy/compatibilita |
| `hash` | `external_mappings.external_hash` | evita update inutili |

Origine AnWP:

- `external_id`: `wp_anwpfl_clubs.club_id`
- `name`: `wp_anwpfl_clubs.title`
- `short_name`: `wp_anwpfl_clubs.abbr`
- `slug`: `wp_anwpfl_clubs.post_name`
- `city`: `wp_anwpfl_clubs.city`
- `country`: `wp_anwpfl_clubs.nationality`
- `primary_stadium_external_id`: `wp_anwpfl_clubs.stadium_id`
- `competition_external_ids[]`: da partite, competizioni o strutture AnWP

### Stadiums

| API | Locale | Regola |
| --- | --- | --- |
| `external_id` | `external_mappings.external_id` | obbligatorio |
| `name` | `fields.name` | obbligatorio |
| `address` | `fields.address` | opzionale |
| `city` | `fields.city` | opzionale |
| `province` | `fields.province` | opzionale |
| `postal_code` | `fields.postal_code` | opzionale |
| `country` | `fields.country` | default `Italia` |
| `latitude` | `fields.latitude` | opzionale |
| `longitude` | `fields.longitude` | opzionale |
| `can_host_11` | `fields.can_host_11` | default true se non noto |
| `can_host_7` | `fields.can_host_7` | default true se non noto |
| `can_host_5` | `fields.can_host_5` | default true se non noto |
| `hash` | `external_mappings.external_hash` | evita update inutili |

Origine AnWP:

- `external_id`: `stadium_id` presente su club e match
- dettagli anagrafici: non presenti in una tabella dedicata nel dump; recuperarli
  tramite `wp_posts` / `wp_postmeta` o classi interne AnWP

### Referees

| API | Locale | Regola |
| --- | --- | --- |
| `external_id` | `external_mappings.external_id` | obbligatorio se disponibile |
| `name` | `referees.name` | obbligatorio |
| `city` | `referees.city` | opzionale |
| `country` | `referees.country` | default `Italia` |
| `can_referee_11` | `referees.can_referee_11` | default true se non noto |
| `can_referee_7` | `referees.can_referee_7` | default true se non noto |
| `can_referee_5` | `referees.can_referee_5` | default true se non noto |
| assente | `referees.rating` | default locale 3 |
| `hash` | `external_mappings.external_hash` | evita update inutili |

Origine AnWP:

- `name`: `wp_anwpfl_matches.referee`
- `external_id`: generato dal plugin come hash stabile del nome normalizzato,
  finche non esiste una sorgente strutturata

### Matches

| API | Locale | Regola |
| --- | --- | --- |
| `external_id` | `external_mappings.external_id` | obbligatorio |
| `season_external_id` | `matches.season_id` | via mapping stagione |
| `competition_external_id` | `matches.competition_id` | via mapping competizione |
| `match_day` | `matches.match_day` | obbligatorio, fallback 1 solo in import assistito |
| `difficulty_rating` | `matches.difficulty_rating` | default locale 3 se assente |
| `home_team_external_id` | `matches.home_team_id` | via mapping squadra |
| `away_team_external_id` | `matches.away_team_id` | via mapping squadra |
| `field_external_id` | `matches.field_id` | via mapping stadio, opzionale |
| `referee_external_id` | `matches.referee_id` | via mapping arbitro, opzionale |
| `match_date` | `matches.match_date` | obbligatorio |
| `match_time` | `matches.match_time` | opzionale |
| `home_goals` | `matches.home_goals` | opzionale |
| `away_goals` | `matches.away_goals` | opzionale |
| `status` | `matches.status` | `scheduled`, `played`, `cancelled` |
| `result_type` | `matches.result_type` | default `played` |
| `walkover_reason` | `matches.walkover_reason` | obbligatorio per tavolino |
| `notes` | `matches.notes` | opzionale |
| `hash` | `external_mappings.external_hash` | evita update inutili |

Origine AnWP:

- `external_id`: `wp_anwpfl_matches.match_id`
- `season_external_id`: `wp_anwpfl_matches.season_id`
- `competition_external_id`: `wp_anwpfl_matches.competition_id`
- `match_day`: `wp_anwpfl_matches.match_week`
- `difficulty_rating`: da `priority` o default locale
- `home_team_external_id`: `wp_anwpfl_matches.home_club`
- `away_team_external_id`: `wp_anwpfl_matches.away_club`
- `field_external_id`: `wp_anwpfl_matches.stadium_id`
- `referee_external_id`: hash stabile di `wp_anwpfl_matches.referee`
- `match_date` / `match_time`: split di `wp_anwpfl_matches.kickoff`
- `home_goals` / `away_goals`: colonne omonime
- `status`: da `finished`, `game_status`, `special_status`
- `result_type`: da `special_status`, valori reali da verificare sui dati

## Ordine Import Consigliato

1. stagioni
2. competizioni
3. stadi
4. squadre
5. relazioni squadra/competizione
6. arbitri
7. partite
8. classifiche, fase successiva

## Regole Anti Duplicato

1. Cercare mapping esistente per `source_id`, `entity_type`, `external_id`.
2. Se non esiste, cercare eventuale candidato locale per chiave naturale:
   - stagione: `name`
   - competizione: `season_id + name`
   - squadra: `name`
   - stadio: `name + city`
   - arbitro: `name`
   - partita: `competition_id + match_day + home_team_id + away_team_id + match_date`
3. Se il candidato e ambiguo, registrare conflitto e richiedere scelta manuale.
4. Dopo la scelta, salvare sempre `external_mappings`.

## Dati Non Mappati In Fase 1

- loghi squadre
- foto stadi
- social squadre
- presenze pubblico
- link YouTube/highlights
- giocatori
- classifiche

Questi dati possono restare nel payload e venire ignorati in import, oppure
essere salvati in futuro in colonne dedicate.
