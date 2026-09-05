# Sicurezza Segreti WordPress Sync

## Chiave API

La chiave `X-API-Key` non viene salvata in chiaro. `SecretCipher` usa
AES-256-GCM con nonce casuale e tag di autenticazione.

Le API locali restituiscono soltanto `api_key_configured`.

## Chiave Di Cifratura

In produzione configurare la variabile ambiente:

```text
FOOTBALL_SYNC_ENCRYPTION_KEY=<segreto-casuale-stabile>
```

Il fallback derivato dal segreto JWT serve solo a mantenere semplice
l'installazione locale. La chiave deve restare stabile tra i deploy.

Con `APP_ENV=production`, la configurazione richiede una chiave dedicata di
almeno 32 caratteri e non utilizza il fallback locale.

## Trasporto

- usare HTTPS in produzione
- mantenere `verify_ssl = true`
- non inserire API key in URL, log o messaggi di errore
- proteggere configurazione e test con `import.manage`
- proteggere le esecuzioni con `import.run`
