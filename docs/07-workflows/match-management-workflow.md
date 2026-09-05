# Match Management Workflow

1. Utente apre partite.
2. Filtra eventualmente la griglia per periodo, competizione, squadra o arbitro.
3. Seleziona competizione e giornata.
4. Seleziona squadre della competizione.
5. Assegna eventualmente uno stadio.
6. Assegna eventualmente un arbitro, visualizzando rating a stelle e distanza
   dallo stadio quando le coordinate sono disponibili.
7. Imposta stato e risultato.
8. Se tavolino, indica reti e motivo.

## Eliminazione

1. Utente con permesso `matches.delete` vede il pulsante Elimina nella griglia.
2. Frontend chiede conferma.
3. Backend elimina la partita tramite `DELETE /api/v1/matches?id=...`.
