# Local Setup

## Requisiti

- XAMPP
- PHP
- MariaDB/MySQL
- Node.js per check JS

## Avvio

Posizionare progetto sotto `htdocs` e configurare database locale.

## Variabili Ambiente

Il contratto completo e disponibile in `.env.example`. Il file non viene
caricato automaticamente: le variabili devono essere configurate in Apache,
PHP o nell'ambiente di deployment.

In locale i valori XAMPP hanno fallback compatibili. In produzione impostare:

- `APP_ENV=production`
- tutte le variabili `DB_*`
- `JWT_SECRET` con almeno 32 caratteri
- `FOOTBALL_SYNC_ENCRYPTION_KEY` con almeno 32 caratteri e diversa dal JWT

Le variabili `GEOCODING_*` consentono di personalizzare endpoint, user agent e
timeout del servizio Nominatim.

Non salvare `.env`, configurazioni `*.local.php`, dump o backup nel repository.
