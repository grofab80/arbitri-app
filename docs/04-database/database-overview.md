# Database Overview

Database MariaDB/MySQL.

Le migrazioni sono conservate in `database/migrations`.

Lo schema installabile da zero e in `database/baseline`; i dati di riferimento
non sensibili sono separati in `database/seeds`.

## Principi

- usare FK dove possibile
- usare indici su colonne filtrate o joinate
- preferire migrazioni additive
- evitare cancellazioni distruttive senza piano di compatibilita
