# Balance

Il bilancio rappresenta il riepilogo economico della stagione corrente.

## Fonte Dati

Il bilancio non e una tabella compilata manualmente.

Viene dedotto dai movimenti della stagione corrente:

- `movements.season_id`
- `movements.amount`
- gerarchia categorie entrate/uscite
- eventuali riferimenti a competizione, squadra o arbitro

## Regole

- il bilancio usa solo i movimenti della stagione in corso
- le entrate derivano dai movimenti classificati sotto `Entrate`
- le uscite derivano dai movimenti classificati sotto `Uscite`
- il saldo e dato da entrate meno uscite
- il bilancio puo essere filtrato per periodo, competizione, squadra e arbitro,
  restando dentro la stagione corrente
- il confronto tra stagioni usa lo snapshot approvato quando disponibile;
  altrimenti ricalcola dai movimenti associati a ciascuna stagione
- il confronto dettagliato tra due stagioni espone anche riepiloghi per
  categoria/sottocategoria e per competizione
- il confronto tra stagioni e una vista separata e non usa i filtri del
  bilancio della stagione corrente
- il confronto dettagliato usa sempre l'intera stagione, senza filtri di
  periodo, squadra, competizione o arbitro
- quando una stagione passa a `chiuso`, il sistema salva uno snapshot ufficiale
  del bilancio in `balance_closures`
- per le stagioni chiuse, il confronto stagioni usa lo snapshot approvato se
  presente; in assenza dello snapshot approvato ricalcola dai movimenti
- una chiusura appena generata nasce come `draft`; l'approvazione formale del
  bilancio verra gestita da un flusso dedicato
- solo uno snapshot approvato potra diventare la fonte storica ufficiale dopo
  l'eventuale eliminazione dei movimenti della stagione storicizzata
- i movimenti di una stagione possono essere eliminati solo dopo approvazione
  dello snapshot, tramite azione admin dedicata
- la variazione saldo nel confronto stagioni confronta solo stagioni con
  movimenti; le stagioni senza movimenti mostrano variazione non disponibile

## Chiusura Bilancio

La chiusura bilancio viene eseguita automaticamente quando:

- una stagione viene impostata a `chiuso`
- una nuova stagione viene impostata a `in_corso` e il sistema chiude la
  precedente stagione in corso

Lo snapshot contiene:

- versione struttura
- data/ora di generazione
- sorgente dati
- dati della stagione
- totale entrate
- totale uscite
- saldo
- numero movimenti
- riepilogo per categoria/sottocategoria
- riepilogo per competizione
- andamento mensile

## Snapshot Approvato

La tabella `balance_closures` prepara il ciclo di approvazione con questi
metadati:

- `approval_status`: `draft` o `approved`
- `approved_at`: data/ora di approvazione
- `approved_by`: utente che approva il bilancio
- `movements_deleted_at`: data/ora futura di eliminazione movimenti storicizzati
- `deleted_movements_count`: numero movimenti eliminati dopo approvazione
- `snapshot_version`: versione della struttura JSON
- `snapshot_hash`: impronta tecnica dello snapshot approvato

Nella fase corrente il confronto stagioni usa gia lo snapshot approvato come
fonte primaria. Dopo approvazione, un admin puo storicizzare la stagione
eliminando i movimenti e registrando `movements_deleted_at` e
`deleted_movements_count`.

La struttura JSON salvata in `snapshot_json` e versionata e segue questo
contratto:

- `snapshot_version`
- `generated_at`
- `source`
- `season`
- `summary`
- `categories`
- `competitions`
- `monthly`

Se una chiusura e gia `approved`, la rigenerazione automatica della chiusura non
deve sovrascrivere importi, JSON, versione, hash o data di chiusura.

Quando uno snapshot approvato viene usato nel confronto, il backend restituisce
la sorgente dati `snapshot`. In caso contrario restituisce `movements`.

## Esempio Struttura

Entrate:

- quote squadre
- tesseramenti
- contributi
- sponsorizzazioni

Uscite:

- compensi arbitri
- rimborsi spese arbitri
- affitto stadi
- spese organizzative
- materiale sportivo

Indicatori:

- totale entrate
- totale uscite
- avanzo o disavanzo
- saldo per categoria
- saldo per competizione
- confronto entrate/uscite/saldo tra stagioni
- variazione del saldo rispetto alla stagione precedente
- filtro per competizione
- filtro per squadra
- filtro per arbitro

## Permessi

- `balance.view`: visualizzazione bilancio
- `balance.export`: esportazione bilancio, da usare in una fase successiva
- `balance.approve`: approvazione snapshot di chiusura bilancio
- `balance.archive`: storicizzazione bilancio approvato con eliminazione dei
  movimenti

## Implementazione Corrente

Il backend espone:

- `GET /api/v1/balance`
- `GET /api/v1/balance-season-comparison`
- `GET /api/v1/balance-season-analysis`
- `PUT /api/v1/balance-closure-approve`
- `PUT /api/v1/balance-closure-archive`

La risposta contiene:

- KPI economici
- opzioni filtro competizione, squadra e arbitro
- riepilogo per categoria/sottocategoria
- riepilogo per competizione
- andamento mensile
- confronto economico tra stagioni
- confronto dettagliato tra due stagioni per categorie e competizioni
- snapshot ufficiale delle stagioni chiuse

## Dati Demo

Per testare il confronto stagioni e disponibile lo script:

- `database/migrations/2026_07_13_seed_2024_2025_comparison_data.sql`

Lo script crea la stagione chiusa `2024/2025`, competizioni, associazioni
squadra/competizione e movimenti economici confrontabili con `2025/2026`.
