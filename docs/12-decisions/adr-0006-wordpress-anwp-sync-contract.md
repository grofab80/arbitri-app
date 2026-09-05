# ADR 0006 - WordPress AnWP Sync Via REST Contract

## Status

Accepted

## Context

Il progetto deve sincronizzare dati sportivi da un sito WordPress con plugin
AnWP Football Leagues.

La struttura interna di AnWP puo cambiare tra versioni e puo usare sia tabelle
dedicate sia `wp_posts`/`wp_postmeta`.

Il dump struttura WordPress `Sql1354899_2.sql` conferma tabelle dedicate per
competizioni, squadre, partite e classifiche, ma non conferma tabelle dedicate
per stagioni, stadi e arbitri.

## Decision

`arbitri-app` non accede direttamente al database WordPress.

Viene definito un plugin WordPress dedicato, `Football Sync API`, che espone
endpoint REST versionati sotto:

```text
/wp-json/football-sync/v1
```

Il plugin WordPress legge AnWP e normalizza i dati in un contratto JSON stabile.
`arbitri-app` consuma solo questo contratto.

## Consequences

Vantaggi:

- minore accoppiamento con schema AnWP
- maggiore sicurezza
- integrazione testabile via HTTP
- possibilita di API key, cache, log e sync incrementale
- evoluzione futura con versionamento API

Svantaggi:

- serve sviluppare e mantenere un plugin WordPress
- alcune informazioni AnWP devono essere mappate e normalizzate
- la verifica dello schema reale deve avvenire sul WordPress installato

## Follow Up

- verificare sui dati reali i post type e i meta usati per stadi e stagioni
- verificare i valori reali di `wp_anwpfl_matches.game_status` e
  `wp_anwpfl_matches.special_status`
- plugin MVP creato in `integrations/wordpress/football-sync-api`
- plugin MVP installato e validato sul sito WordPress reale
- sync incrementale, cache e logging implementati nella versione `0.2.0`
- introdurre mapping esterno/locale in `arbitri-app`
- consumare il contratto incrementale da `arbitri-app`
