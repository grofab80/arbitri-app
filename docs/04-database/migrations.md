# Migrations

Le migrazioni sono script SQL in `database/migrations`.

La baseline corrente e `baseline:2026-09-05`. Il ledger applicativo e la
classificazione degli script sono documentati in
`database/migrations/README.md`.

## Regole

- una migrazione deve fare una cosa chiara
- usare `START TRANSACTION` e `COMMIT`
- aggiungere commento iniziale
- preferire modifiche additive
- includere query di verifica quando utile
- registrare il nome file in `schema_migrations`

## Migrazioni Rilevanti

- normalizzazione squadre/competizioni
- introduzione stagioni nei movimenti
- standardizzazione ruoli
- introduzione partite
- difficolta partita e base designazioni arbitrali
- visibilita designazioni per profilo user
- risultati partite
- arbitro su partita
- abilitazioni arbitro
- stadi e relazione con squadre/partite
- geocodifica stadi
- stato esplicito delle stagioni
- rating arbitri
- residenza arbitri
- RBAC profili e permessi
- indirizzo strutturato stadi
- rimozione ruolo legacy utenti
- permesso eliminazione utenti
- vincolo permessi eliminazione solo admin
- classifiche competizioni
- penalizzazioni classifiche competizioni
- profilo segreteria e permessi bilancio
- dati demo 2024/2025 per confronto stagioni bilancio
- metadati approvazione snapshot bilancio
- permesso approvazione snapshot bilancio
- permesso storicizzazione bilancio approvato
- sorgenti esterne, mapping e storico sincronizzazioni WordPress
- correzioni mapping WordPress e conteggio record ignorati
- avanzamento osservabile delle sincronizzazioni WordPress
- separazione tra stato connessione WordPress ed esito sincronizzazione
- normalizzazione e riallineamento stagione corrente importata da WordPress
