# Referees Page

File:

- `admin/referees.php`
- `admin/js/referees.js`

## Funzioni

- lista arbitri
- creazione/modifica/eliminazione
- residenza arbitro
- abilitazioni 11, 7, 5
- rating manuale da 1 a 5
- visualizzazione rating con stelle
- rating riusato nella select arbitro della pagina partite
- gestione disponibilita arbitro tramite azione calendario in griglia

## Residenza

La modale arbitro consente di salvare:

- indirizzo
- CAP
- citta
- provincia
- nazione

La griglia mostra una sintesi della residenza.

La modale include il bottone "Verifica indirizzo" per geocodificare un arbitro
gia salvato.

Se l'indirizzo viene modificato, prima va salvato l'arbitro e poi va rilanciata
la verifica.

## Disponibilita

La griglia arbitri mostra un pulsante calendario per chi possiede
`referee_availabilities.view`.

La modale disponibilita contiene due tab:

- `Ricorrenti`: giorno settimana, ora inizio, ora fine, stato e note
- `Date specifiche`: data, ora inizio, ora fine, stato e note

Le azioni di creazione, modifica ed eliminazione usano i permessi granulari:

- `referee_availabilities.create`
- `referee_availabilities.edit`
- `referee_availabilities.delete`

Le cancellazioni usano la modale di conferma applicativa.
