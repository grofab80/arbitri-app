# Teams

Una squadra puo partecipare a piu competizioni.

## Campi Principali

- `id`
- `name`
- `field_id`
- `competition_id` legacy
- `created_at`

## Relazione Competizioni

La relazione vera e `competition_teams`.

Il campo `teams.competition_id` contiene la prima competizione selezionata per compatibilita con dati o schermate legacy.

## Regole

- una squadra deve avere almeno una competizione
- una squadra puo avere uno stadio assegnato, non obbligatorio
- non puo essere cancellata se usata da movimenti o partite
