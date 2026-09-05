# Competitions

Una competizione e un campionato o torneo.

## Campi Principali

- `id`
- `season_id`
- `name`
- `type`
- `football_type`
- `season`
- `created_at`

## Regole

- appartiene a una stagione
- `type` puo essere `campionato` o `torneo`
- `football_type` puo essere `11`, `7` o `5`
- non puo essere cancellata se usata da squadre, movimenti o partite
- non si cambia stagione se ci sono partite collegate
- le classifiche sono previste solo per competizioni di tipo `campionato`

## Classifica

La classifica e considerata un attributo della competizione, non una pagina
indipendente.

La tabella `competition_standings` contiene una riga per ogni squadra della
competizione.

Campi principali:

- `competition_id`
- `team_id`
- `rank_position`
- `played`
- `won`
- `drawn`
- `lost`
- `goals_for`
- `goals_against`
- `penalty_points`
- `points`
- `notes`

La gestione manuale della classifica richiede il permesso:

- `competitions.standings.manage`

## Penalizzazioni

La colonna `penalty_points` contiene eventuali penalizzazioni applicate alla
squadra nella competizione.

Regole:

- il valore puo essere negativo, ad esempio `-3`
- il totale `points` include gia la penalizzazione
- il ricalcolo da partite mantiene le penalizzazioni esistenti
- l'ordinamento usa i punti finali, quindi gia comprensivi delle penalizzazioni
- il ricalcolo considera le partite non annullate con risultato completo
