# Documentazione Arbitri App

Questa cartella contiene la documentazione tecnica, funzionale e operativa del progetto.

La documentazione deve essere mantenuta aggiornata a ogni intervento significativo. Quando viene introdotto un nuovo modulo, una nuova regola di business, una nuova tabella o una nuova API, va aggiornato il documento corrispondente.

## Struttura

- `01-overview`: visione generale del prodotto.
- `02-architecture`: architettura, flussi e linee guida MVC.
- `03-domain`: dominio funzionale e regole di business.
- `04-database`: schema, relazioni, vincoli e migrazioni.
- `05-api`: documentazione degli endpoint.
- `06-frontend`: pagine admin, UI e convenzioni frontend.
- `07-workflows`: flussi operativi utente.
- `08-patterns`: pattern da seguire per nuovi sviluppi.
- `09-security`: sicurezza applicativa.
- `10-quality`: qualita codice, refactoring e test.
- `11-operations`: setup, migrazioni e troubleshooting.
- `12-decisions`: ADR, cioe decisioni architetturali.
- `templates`: template per nuova documentazione.

## Regola Di Manutenzione

Ogni nuova funzionalita deve aggiornare almeno:

- il modulo in `03-domain`
- gli endpoint in `05-api`
- la pagina frontend in `06-frontend`
- il workflow in `07-workflows`, se cambia il comportamento utente
- un ADR in `12-decisions`, se introduce una decisione architetturale rilevante

Il flusso Git e la regola sulle operazioni esplicite sono descritti in
`11-operations/version-control.md`. I controlli automatici e la protezione del
branch principale sono descritti in `11-operations/continuous-integration.md`.
