# Database

La cartella contiene gli artefatti necessari per installare, aggiornare e
popolare il database di Arbitri App.

## Struttura

- `baseline/2026_09_05_schema.sql`: schema completo senza dati
- `seeds/2026_09_05_reference_data.sql`: soli dati applicativi di riferimento
- `migrations/`: evoluzioni storiche e future dello schema
- `bootstrap_admin.php`: creazione sicura del primo amministratore

La baseline contiene 27 tabelle, compreso il ledger `schema_migrations`. Non
contiene utenti, stagioni, competizioni, squadre, arbitri, partite, movimenti,
notifiche, configurazioni remote o credenziali.

## Nuova Installazione

1. Creare un database vuoto con charset `utf8mb4`.
2. Importare `baseline/2026_09_05_schema.sql`.
3. Importare `seeds/2026_09_05_reference_data.sql`.
4. Applicare soltanto le migration successive alla baseline.
5. Creare il primo amministratore con `bootstrap_admin.php`.

Il seed configura tre profili, il catalogo permessi, le associazioni RBAC, la
tassonomia contabile e una sorgente WordPress vuota e disabilitata.

## Dati Demo

Gli script con prefisso `seed_` sono facoltativi e destinati allo sviluppo. La
migration `2026_07_13_rebuild_financial_categories.sql` e distruttiva e non
deve essere eseguita su ambienti con dati da conservare.
