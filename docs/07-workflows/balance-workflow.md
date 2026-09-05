# Balance Workflow

1. Utente con permesso `balance.view` apre `Bilancio`.
2. Il frontend chiama `GET /api/v1/balance`.
3. Il backend legge la stagione corrente.
4. Il backend aggrega i movimenti della stagione corrente.
5. Entrate e uscite vengono dedotte dalla gerarchia categorie.
6. La pagina mostra totale entrate, totale uscite e saldo.
7. I dettagli vengono raggruppati per categoria e competizione.
8. L'utente puo filtrare il periodo dentro la stagione corrente.
9. L'utente puo filtrare una competizione tra quelle presenti nei movimenti.
10. L'utente puo filtrare una squadra tra quelle presenti nei movimenti.
11. L'utente puo filtrare un arbitro tra quelli presenti nei movimenti.
12. Il confronto tra stagioni e disponibile in un tab dedicato e non usa i
    filtri applicati al bilancio della stagione corrente.

## Confronto Stagioni

1. L'utente apre il tab `Confronto stagioni`.
2. Il frontend chiama `GET /api/v1/balance-season-analysis`.
3. Il backend propone stagione base e stagione confronto.
4. L'utente puo cambiare le due stagioni e aggiornare il confronto.
5. Per ogni stagione, il backend usa lo snapshot approvato se presente.
6. Se lo snapshot approvato non esiste, il backend ricalcola dai movimenti.
7. Il backend aggrega entrate, uscite, saldo e numero movimenti per entrambe
   le stagioni.
8. Il backend calcola anche il confronto per categoria/sottocategoria e per
   competizione dalla stessa sorgente usata per il riepilogo.
9. La pagina mostra un grafico comparativo, il riepilogo generale, una tabella
   per categorie e una tabella per competizioni.
10. La tabella riepilogo mostra stato e fonte dati di ogni stagione.
11. Le variazioni positive e negative vengono evidenziate con colori distinti.
12. Le tabelle categorie e competizioni sono ordinate per variazione assoluta
   del saldo, dalla piu rilevante alla meno rilevante.

## Chiusura Bilancio

1. Admin imposta una stagione a `chiuso`, oppure avvia una nuova stagione.
2. Il backend calcola il bilancio completo della stagione chiusa.
3. Il backend salva o aggiorna uno snapshot versionato in `balance_closures`.
4. Lo snapshot viene creato in stato `draft`.
5. Lo snapshot include riepilogo, categorie, competizioni e andamento mensile.
6. Se una chiusura e gia `approved`, la rigenerazione automatica non la
   sovrascrive.
7. Una fase successiva introdurra l'approvazione esplicita dello snapshot.
8. Solo dopo approvazione lo snapshot potra diventare il riferimento storico
   ufficiale per il confronto stagioni e per l'eventuale eliminazione dei
   movimenti della stagione storicizzata.

## Approvazione Bilancio

1. Admin apre il tab `Confronto stagioni`.
2. Seleziona come stagione base una stagione chiusa.
3. Se lo snapshot non e gia approvato, il frontend mostra il comando di
   approvazione con permesso `balance.approve`.
4. Il frontend chiede conferma con modale.
5. Il backend genera lo snapshot mancante o aggiorna quello in `draft`.
6. Il backend imposta `approval_status = approved`, `approved_at` e
   `approved_by`.
7. I confronti successivi usano lo snapshot approvato come fonte primaria.
8. L'approvazione non elimina ancora i movimenti della stagione.

## Storicizzazione Bilancio

1. Admin apre il tab `Confronto stagioni`.
2. Seleziona come stagione base una stagione chiusa con snapshot approvato.
3. Se i movimenti non sono gia stati eliminati, il frontend mostra il comando
   di storicizzazione con permesso `balance.archive`.
4. Il frontend chiede conferma con modale.
5. Il backend verifica che lo snapshot sia `approved`.
6. Il backend elimina i movimenti della stagione.
7. Il backend registra `movements_deleted_at` e `deleted_movements_count`.
8. I confronti successivi continuano a usare lo snapshot approvato.

## Export CSV

1. Utente con `balance.export` clicca `Esporta CSV`.
2. Il frontend scarica `GET /api/v1/balance-export` con JWT.
3. Il backend applica gli stessi filtri periodo/competizione/squadra/arbitro
   della pagina.
4. Il backend genera un CSV dalla stessa sorgente dati del bilancio.
5. Il browser avvia il download del file.
