# Movements

I movimenti rappresentano entrate e uscite economiche.

## Campi Principali

- `id`
- `season_id`
- `category_id`
- `amount`
- `movement_date`
- `description`
- `competition_id`
- `team_id`
- `referee_id`
- `created_at`

## Regole

- ogni movimento viene associato alla stagione corrente
- la data del movimento deve rientrare tra inizio e fine della stagione corrente
- i campi competizione/squadra/arbitro dipendono dalla categoria scelta
- le categorie determinano se un riferimento e obbligatorio, consentito o vietato
- i movimenti sono la fonte dati della Prima Nota e del Bilancio

## Categorie

La tassonomia finanziaria e documentata in:

- `docs/03-domain/financial-categories.md`

I movimenti devono usare solo categorie di livello 4.
