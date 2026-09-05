# Competition Management Workflow

1. Admin apre competizioni.
2. Crea o modifica competizione.
3. Seleziona stagione, tipo e calcio.
4. Backend blocca duplicati e operazioni incoerenti.

## Classifica Campionato

1. La classifica e disponibile solo per competizioni di tipo `campionato`.
2. Il backend legge la classifica tramite `GET /api/v1/competition-standings`.
3. Alla lettura, eventuali squadre mancanti vengono inizializzate da
   `competition_teams`.
4. La modifica manuale usa `PUT /api/v1/competition-standings`.
5. Il salvataggio richiede `competitions.standings.manage`.
6. Il ricalcolo automatico usa `PUT /api/v1/competition-standings-recalculate`.
7. Il ricalcolo considera solo partite non annullate con risultato valorizzato.
8. Eventuali penalizzazioni sono espresse come punti negativi, ad esempio `-3`.
9. Il ricalcolo aggiorna statistiche e punti partita, ma mantiene le
   penalizzazioni esistenti.
