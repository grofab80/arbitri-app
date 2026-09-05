# Business Rules

## Stagione Corrente

I movimenti sono associati alla stagione corrente.

Le dashboard e le viste operative devono mostrare dati della stagione corrente, salvo filtri espliciti futuri.

La stagione corrente corrisponde alla stagione con stato `in_corso`.

Una sola stagione puo essere `in_corso` per volta.

## Squadre E Competizioni

Una squadra puo partecipare a piu competizioni.

La relazione canonica e `competition_teams`.

Il campo legacy `teams.competition_id` resta temporaneamente per compatibilita.

## Partite

Ogni partita appartiene a una competizione.

Ogni competizione appartiene a una stagione.

Una partita puo avere:

- risultato regolare
- vittoria a tavolino casa
- vittoria a tavolino trasferta

Se la partita e a tavolino, devono essere presenti reti e motivo.

## Arbitri

Un arbitro puo essere abilitato a calcio a 11, 7 e/o 5.

Un arbitro deve avere almeno una abilitazione.

## Stadi

Uno stadio puo essere abilitato a ospitare calcio a 11, 7 e/o 5.

Uno stadio deve avere almeno una tipologia ospitabile.

Solo gli stadi attivi vengono proposti nella selezione partita.

## Ruoli

- `admin`: gestisce anagrafiche, stagioni, competizioni, squadre, arbitri, stadi, movimenti
- `user`: puo accedere alle funzioni operative consentite, come la gestione partite
