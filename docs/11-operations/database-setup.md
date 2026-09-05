# Database Setup

## Nuova Installazione

1. Creare un database vuoto con charset `utf8mb4`.
2. Configurare le variabili `DB_*` descritte in `.env.example`.
3. Importare `database/baseline/2026_09_05_schema.sql`.
4. Importare `database/seeds/2026_09_05_reference_data.sql`.
5. Applicare soltanto le migration successive alla baseline.
6. Installare le dipendenze con `composer install`.
7. Creare il primo utente amministratore.
8. Creare e avviare la prima stagione dall'interfaccia.

Esempio PowerShell per il primo amministratore:

```powershell
$env:ADMIN_USERNAME = 'admin'
$env:ADMIN_PASSWORD = 'una-password-casuale-di-almeno-12-caratteri'
$env:ADMIN_FIRST_NAME = 'Nome'
$env:ADMIN_LAST_NAME = 'Cognome'
$env:ADMIN_EMAIL = 'admin@example.test'
php database/bootstrap_admin.php
```

Le variabili `ADMIN_*` sono lette soltanto dal comando CLI e non vengono
salvate nei file del progetto.

## Database Esistente

Non importare la baseline sopra un database popolato. Eseguire prima un backup,
quindi applicare soltanto le migration mancanti. Per introdurre il registro su
un'installazione aggiornata allo stato del 5 settembre 2026, eseguire
`2026_09_05_add_schema_migration_ledger.sql`.

## Verifica Minima

- 27 tabelle dopo l'import completo
- 3 profili di sistema
- 54 permessi attivi
- 72 categorie contabili
- una sorgente WordPress disabilitata e senza credenziali
- nessun utente o dato operativo prima del bootstrap amministratore
