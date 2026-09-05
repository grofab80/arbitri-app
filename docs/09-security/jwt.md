# JWT

JWT viene usato per autenticare le API.

La dipendenza supportata e `firebase/php-jwt` 7.x.

## Regole

- secret fuori dal codice tramite `JWT_SECRET`
- scadenza token configurata
- ruoli inclusi nel payload
- validazione su ogni rotta protetta

Con `APP_ENV=production`, l'applicazione rifiuta l'avvio se `JWT_SECRET` non e
presente o contiene meno di 32 caratteri. Il fallback nel codice e riservato
esclusivamente allo sviluppo locale.
