# Migrations Runbook

## Prima

- eseguire un backup verificato del database
- leggere dipendenze, impatto e query di verifica dello script
- controllare `schema_migrations`
- distinguere migration strutturali, seed demo e reset distruttivi

## Nuove Installazioni

Importare la baseline e il seed di riferimento. Le migration fino al
3 settembre 2026 sono gia assorbite e non devono essere rieseguite.

## Installazioni Esistenti

Applicare in ordine cronologico soltanto gli script mancanti. Il progetto non
ha ancora un runner automatico: l'esecuzione resta manuale ed esplicita.

Ogni nuova migration deve registrarsi al termine in `schema_migrations` usando
come versione il proprio nome file.

## Dopo

- eseguire la query di verifica della migration
- controllare la nuova riga nel ledger
- eseguire lint e smoke test interessati
- verificare manualmente la pagina coinvolta
- aggiornare la documentazione

Il registro completo e le convenzioni sono in
`database/migrations/README.md`.
