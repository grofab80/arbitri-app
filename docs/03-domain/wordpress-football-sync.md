# WordPress Football Sync

## Scopo

Il modulo WordPress Football Sync definisce il ponte dati tra un sito WordPress
con AnWP Football Leagues e `arbitri-app`.

WordPress/AnWP resta la sorgente esterna dei dati sportivi pubblicati sul sito.
`arbitri-app` resta il gestionale operativo per prima nota, bilancio,
designazioni, permessi e gestione interna.

## Principio Guida

`arbitri-app` non deve leggere direttamente il database WordPress.

L'integrazione deve passare da un plugin WordPress dedicato, ad esempio
`Football Sync API`, che espone un contratto REST stabile. Questo evita di
accoppiare il gestionale alla struttura interna di AnWP, che puo cambiare tra
versioni.

## Stato Schema AnWP

E stato analizzato il dump struttura `Sql1354899_2.sql` del database WordPress.
Il sito usa prefisso `wp_` e contiene tabelle dedicate AnWP.

Tabelle AnWP presenti nel dump:

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

Non risulta una tabella dedicata `wp_anwpfl_stadiums`.
Gli stadi sono referenziati tramite `stadium_id` in `wp_anwpfl_clubs` e
`wp_anwpfl_matches`; il dettaglio anagrafico dello stadio deve quindi essere
letto da `wp_posts` / `wp_postmeta` o dalle API/classi interne AnWP.

Non risulta una tabella dedicata `wp_anwpfl_seasons`.
Le stagioni sono presenti come ID/testo su `wp_anwpfl_competitions` e
`wp_anwpfl_matches`; il dettaglio anagrafico deve essere ricostruito da AnWP,
`wp_posts` / `wp_postmeta`, o dalle classi interne del plugin.

Non risulta una tabella dedicata agli arbitri.
Nelle partite e presente il campo testuale `referee`; in Fase 2 puo essere
esposto come elenco arbitri dedotto dalle partite, con ID esterno calcolato.

## Entita Minime Da Sincronizzare

La prima integrazione deve limitarsi alle entita utili per `arbitri-app`:

- stagioni
- competizioni
- squadre
- stadi
- arbitri
- partite

Origine minima dai dati reali AnWP:

- stagioni: da `wp_anwpfl_competitions.season_ids`,
  `wp_anwpfl_competitions.season_text`, `wp_anwpfl_matches.season_id`
- competizioni: da `wp_anwpfl_competitions`
- squadre: da `wp_anwpfl_clubs`
- stadi: da `stadium_id` + dettaglio WordPress/AnWP
- arbitri: da `wp_anwpfl_matches.referee`
- partite: da `wp_anwpfl_matches`

Entita rimandate:

- giocatori
- classifiche
- statistiche marcatori
- staff
- media/highlights avanzati

## Responsabilita WordPress

Il plugin WordPress deve:

- leggere dati da AnWP tramite classi interne quando disponibili
- usare query dirette sulle tabelle AnWP solo dove e necessario o piu efficiente
- normalizzare i dati in JSON stabile
- proteggere gli endpoint tramite API key
- supportare paginazione
- supportare sync incrementale tramite `updated_after` a partire dalla Fase 3
- restituire `external_id`, `updated_at` e `hash` per ogni record

## Responsabilita Arbitri-App

`arbitri-app` deve:

- configurare la sorgente WordPress
- scaricare record tramite API REST
- mantenere mapping tra ID esterno e ID locale
- evitare duplicati
- aggiornare solo record modificati
- registrare esiti della sincronizzazione
- registrare avanzamento e heartbeat delle esecuzioni in corso
- segnalare errori o dati incompleti tramite dashboard/notifiche

## Regole Di Mapping

- Ogni record importato deve conservare `external_source` e `external_id`.
- Il matching principale deve essere per mapping esterno, non per nome.
- Il matching per nome puo essere usato solo come fallback guidato.
- I record locali modificati manualmente devono poter essere protetti da
  sovrascrittura automatica in una fase successiva.
- Le cancellazioni WordPress non devono eliminare subito i record locali:
  inizialmente devono marcare il mapping come non piu presente nella sorgente.

## Campi Standard

Ogni payload sincronizzabile deve includere:

- `external_id`
- `updated_at`
- `hash`

Campi consigliati:

- `source_url`
- `status`
- `raw`

`raw` e opzionale e serve solo per diagnostica, non per logica applicativa.

## Roadmap Dopo Fase 1

Fase 2 - completata nel repository:

- plugin WordPress MVP in `integrations/wordpress/football-sync-api`
- API key configurabile o definita in `wp-config.php`
- endpoint `/info`
- endpoint stagioni, competizioni, squadre, stadi, arbitri e partite
- paginazione, filtri principali e hash stabile
- guida di installazione e smoke test del normalizzatore

Il plugin Fase 2 e stato installato e validato sul sito WordPress di produzione.

Fase 3 - completata nel repository:

- endpoint `/sync`
- indice persistente degli hash e tombstone cancellazioni
- cursore incrementale esclusivo `updated_after`
- lock contro refresh concorrenti
- logging chiamate con IP anonimizzato
- retention automatica dei log
- cache WordPress Transient API con TTL configurabile
- invalidazione cache su refresh sync e modifiche WordPress

La versione `0.2.1` estende il riconoscimento delle discipline reali e include
nel payload ID/nome lega per la risoluzione assistita delle ambiguita.

Fase 4:

- modulo import in `arbitri-app`
- tabelle mapping esterni/locali
- pagina configurazione sorgente
- import manuale e incrementale
- correzioni persistenti per disciplina/stagione e record ignorati

Fase 5:

- automazione cron
- notifiche errori sync
- eventuali webhook
