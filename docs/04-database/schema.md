# Schema

## Tabelle

- `users`
- `profiles`
- `permissions`
- `profile_permissions`
- `seasons`
- `competitions`
- `competition_standings`
- `teams`
- `competition_teams`
- `referees`
- `fields`
- `matches`
- `designations`
- `referee_team_blacklist`
- `referee_availabilities`
- `categories`
- `movements`
- `balance_closures`
- `operational_alert_snapshots`
- `operational_notifications`
- `operational_notification_reads`
- `external_sources`
- `external_mappings`
- `external_sync_overrides`
- `sync_runs`
- `sync_run_items`
- `schema_migrations`

## Tabelle Di Relazione

- `competition_teams`: relazione molti-a-molti tra competizioni e squadre
- `profile_permissions`: relazione molti-a-molti tra profili e permessi
- `operational_notification_reads`: stato lettura notifiche per utente
- `schema_migrations`: registro della baseline e delle evoluzioni successive

## Baseline

La baseline `2026_09_05` contiene la struttura completa delle 27 tabelle. I
dati applicativi indispensabili sono separati nel relativo seed di riferimento.
Le installazioni nuove non eseguono le migration storiche gia assorbite.

## Designazioni

- `matches.difficulty_rating`: difficolta partita da 1 a 5
- `designations`: designazione arbitro per partita
- `referee_team_blacklist`: coppie arbitro/squadra escluse dalla proposta
  automatica

## Snapshot Bilancio

- `balance_closures`: snapshot di chiusura del bilancio stagionale
- `balance_closures.approval_status`: stato dello snapshot, `draft` o `approved`
- `balance_closures.approved_by`: utente che approva lo snapshot
- `balance_closures.movements_deleted_at`: data futura di eliminazione movimenti
  storicizzati

## Notifiche Operative

- `operational_alert_snapshots`: ultimo conteggio noto degli alert operativi
- `operational_notifications`: eventi generati quando un alert aumenta
- `operational_notification_reads`: stato di lettura o dismiss per utente
