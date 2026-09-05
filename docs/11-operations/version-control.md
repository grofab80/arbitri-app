# Controllo Versione

Il repository ufficiale del progetto e `grofab80/arbitri-app` su GitHub.

## Branch

- `main`: linea stabile di sviluppo e base delle release
- `feature/<nome>`: nuove funzionalita isolate
- `fix/<nome>`: correzioni applicative
- `release/<versione>`: preparazione facoltativa di una release

I branch vengono integrati in `main` dopo verifica del codice e della
documentazione. Le versioni pubblicate usano tag Semantic Versioning, a partire
da `v0.1.0-alpha.1`.

## Operazioni Esplicite

Le modifiche ai file restano locali finche l'utente non richiede esplicitamente
un'operazione Git. Codex non esegue automaticamente:

- creazione o cambio branch
- commit
- push
- merge o rebase
- creazione di tag o release

Una richiesta di sviluppo non implica il caricamento su GitHub.

## Contenuto Del Repository

Devono essere versionati codice sorgente, migrazioni, documentazione, asset
applicativi e lock file delle dipendenze. Segreti, configurazioni locali, dump,
backup, log e dipendenze ricostruibili restano esclusi tramite `.gitignore`.

Prima di ogni pubblicazione applicare la `release-checklist.md`.
