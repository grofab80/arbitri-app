# Smoke Tests

Gli smoke test sono script PHP standalone pensati per verifiche rapide su un
database locale o di sviluppo.

## Regole

- ogni smoke test deve eseguire le modifiche dentro una transazione
- ogni smoke test deve fare `ROLLBACK` alla fine
- un test deve uscire con codice `0` se passa
- un test deve uscire con codice `1` se fallisce
- i messaggi `[PASS]` indicano i blocchi verificati

## Designazioni

File:

- `tests/smoke/designations_smoke.php`

Comando:

```bash
php tests/smoke/designations_smoke.php
```

Copertura:

- blacklist add/list/remove
- modifica manuale designazione
- conferma designazione
- generazione proposta automatica
- azioni massive su proposte automatiche
- conflitto orario nello stesso giorno
- rispetto blacklist durante la generazione

Prerequisiti:

- una stagione corrente
- almeno una competizione con due squadre
- almeno tre arbitri abilitati alla disciplina della competizione scelta

## Disponibilita Arbitri

File:

- `tests/smoke/referee_availabilities_smoke.php`

Comando:

```bash
php tests/smoke/referee_availabilities_smoke.php
```

Copertura:

- validazione disponibilita ricorrente e puntuale
- precedenza indisponibilita puntuale
- generazione designazione vincolata alla disponibilita
- payload frontend con arbitri disponibili/non disponibili

## Notifiche Operative

File:

- `tests/smoke/operational_notifications_smoke.php`

Comando:

```bash
php tests/smoke/operational_notifications_smoke.php
```

Copertura:

- baseline iniziale senza notifiche retroattive
- generazione notifica su incremento alert
- filtro backend per permessi utente
- visibilita completa admin
- marcatura singola e massiva come letta
- assenza di duplicati quando non ci sono nuovi incrementi

Prerequisiti:

- migration notifiche operative applicata
- una stagione corrente
- permesso `fields.edit` presente nel catalogo RBAC

## WordPress Football Sync

File:

- `tests/smoke/wordpress_football_sync_normalizer_smoke.php`

Comando:

```bash
php tests/smoke/wordpress_football_sync_normalizer_smoke.php
```

Copertura:

- deduzione stagioni da competizioni e partite
- mapping tipo e disciplina competizione
- ID deterministico arbitro dedotto
- normalizzazione data/ora e stato partita
- coerenza hash record

Il test non richiede WordPress o database perche usa stub minimi delle funzioni
WordPress necessarie al normalizzatore.

### Sync Incrementale Fase 3

File:

- `tests/smoke/wordpress_football_sync_phase3_smoke.php`

Comando:

```bash
php tests/smoke/wordpress_football_sync_phase3_smoke.php
```

Copertura:

- riuso cache Transient API
- invalidazione cache tramite generazione
- monotonicita del cursore `indexed_at`
- confronto esclusivo `updated_after`
- presenza tombstone cancellazioni

Il test non sostituisce la verifica di `dbDelta` e del primo popolamento indice
sul database WordPress reale.
## WordPress Sync Import Fase 4

```bash
php tests/smoke/wordpress_sync_import_phase4_smoke.php
```

Verifica cifratura della chiave API, rilevamento manomissioni, validazione
sorgente e controllo URL del client HTTP senza effettuare chiamate di rete.
