# Arbitri App

Arbitri App e una web application gestionale per associazioni sportive. Il
progetto integra gestione sportiva, amministrativa e designazioni arbitrali in
un unico pannello AdminLTE.

Il software e attualmente in sviluppo incrementale.

## Moduli Principali

- stagioni, competizioni, squadre, stadi e partite
- arbitri, disponibilita e designazioni
- prima nota, categorie finanziarie e bilancio
- utenti, profili e permessi RBAC
- notifiche e dashboard operative
- sincronizzazione non distruttiva da WordPress/AnWP Football Leagues

## Stack

- PHP 8.2 con architettura MVC custom
- MariaDB/MySQL
- JavaScript, jQuery, Bootstrap e AdminLTE
- DataTables, Chart.js e jquery-toast-plugin
- JWT per autenticazione API
- Composer per le dipendenze PHP

## Requisiti

- PHP 8.2 o compatibile
- MariaDB/MySQL
- Apache con `mod_rewrite`
- Composer
- Node.js per i controlli sintattici JavaScript

## Installazione Locale

1. Posizionare il progetto sotto la document root, ad esempio in
   `C:\xampp\htdocs\arbitri-app`.
2. Configurare le variabili descritte in `.env.example` nell'ambiente PHP.
3. Installare le dipendenze con `composer install`.
4. Preparare il database seguendo `docs/11-operations/database-setup.md`.
5. Creare il primo amministratore con `php database/bootstrap_admin.php`.
6. Aprire `/arbitri-app` dal browser.

Il file `.env.example` e soltanto un contratto di configurazione e non viene
caricato automaticamente dall'applicazione.

## Qualita

Controllo PHP:

```bash
php -l percorso/file.php
```

Controllo JavaScript:

```bash
node --check percorso/file.js
```

Gli smoke test disponibili sono descritti in
`docs/11-operations/smoke-tests.md`.

## Documentazione

La documentazione tecnica e funzionale si trova in `docs/README.md`. Ogni
sviluppo deve aggiornare i documenti relativi a dominio, API, frontend e
workflow.

Il plugin WordPress distribuibile si trova in
`integrations/wordpress/football-sync-api`.

## Configurazione E Sicurezza

- non salvare credenziali o chiavi reali nel repository
- usare `APP_ENV=production` negli ambienti pubblici
- configurare chiavi distinte per `JWT_SECRET` e
  `FOOTBALL_SYNC_ENCRYPTION_KEY`
- conservare dump e backup fuori dalle directory versionate

## Versione

La versione prevista per la prima baseline Git e `0.1.0-alpha.1`.

## Licenza

Software proprietario. Tutti i diritti riservati.
