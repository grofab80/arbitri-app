# Matches API

## Endpoint

- `GET /matches`
- `POST /matches`
- `PUT /matches`
- `DELETE /matches`

## Regole

- competizione obbligatoria
- giornata obbligatoria
- difficolta opzionale in input, valida da 1 a 5, default 3
- squadre obbligatorie e diverse
- arbitro opzionale
- stadio opzionale
- risultato a tavolino richiede reti e motivo

## DELETE /matches

Elimina una partita esistente.

### Query

- `id`

### Autorizzazione

Richiede:

- `matches.delete`
