# Seed Data

## Dati Di Riferimento

`database/seeds/2026_09_05_reference_data.sql` fa parte dell'installazione e
contiene esclusivamente:

- profili `admin`, `user` e `segreteria`
- catalogo permessi e associazioni RBAC predefinite
- tassonomia delle categorie contabili
- sorgente WordPress vuota e disabilitata
- identificativo della baseline nel migration ledger

Non contiene utenti, password, chiavi API o dati operativi.

## Dati Dimostrativi

Gli script `seed_*` dentro `database/migrations` sono facoltativi e destinati
agli ambienti locali. Non devono essere applicati automaticamente in produzione.

Gli script locali che creano utenti di test sono esclusi dal repository. Gli
utenti devono essere creati dall'interfaccia o dal bootstrap amministratore.

Il primo amministratore viene creato con `database/bootstrap_admin.php`, usando
variabili d'ambiente temporanee e una password di almeno 12 caratteri.
