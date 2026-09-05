# Migration Register

## Baseline Corrente

`baseline:2026-09-05` assorbe lo stato di tutte le migration fino al
`2026_09_03_separate_wordpress_connection_status.sql` incluso.

Su una nuova installazione tali script non devono essere rieseguiti. Restano
nel repository per audit e per aggiornare installazioni precedenti alla
baseline.

La migration `2026_09_05_add_schema_migration_ledger.sql` aggiunge il ledger a
un database esistente. Non serve su un database creato dalla baseline.

## Classificazione

- migration strutturali e di permessi: applicabili in ordine cronologico
- file `seed_*`: dati dimostrativi facoltativi
- gli script locali con credenziali di test non vengono versionati
- `2026_07_13_rebuild_financial_categories.sql`: reset locale distruttivo

## Regole Per Le Nuove Migration

- usare il formato `YYYY_MM_DD_descrizione.sql`
- rendere esplicite dipendenze, impatto e query di verifica
- non inserire password, chiavi API o dati personali
- racchiudere le modifiche in transazione quando MariaDB lo consente
- registrare la versione in `schema_migrations` al termine dello script
- aggiornare documentazione di schema e runbook

Esempio di registrazione:

```sql
INSERT INTO schema_migrations (version, description)
VALUES ('2026_09_06_example.sql', 'Example change')
ON DUPLICATE KEY UPDATE description = VALUES(description);
```

L'applicazione non dispone ancora di un migration runner automatico: controllo
e applicazione degli script restano operazioni esplicite.
