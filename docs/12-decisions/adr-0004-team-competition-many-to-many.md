# ADR 0004: Team Competition Many To Many

## Stato

Accettata

## Contesto

Una squadra puo partecipare a piu competizioni.

## Decisione

Usare `competition_teams` come relazione canonica molti-a-molti.

## Conseguenze

- `teams.competition_id` resta solo legacy
- nuove query devono usare `competition_teams`
- in futuro il campo legacy potra essere rimosso
