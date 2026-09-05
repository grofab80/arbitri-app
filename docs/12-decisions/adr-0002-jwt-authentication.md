# ADR 0002: JWT Authentication

## Stato

Accettata

## Contesto

Le API admin richiedono autenticazione.

## Decisione

Usare JWT per autenticare chiamate API.

## Conseguenze

- frontend deve inviare token
- backend deve validare token e ruolo
- attenzione a XSS se token e salvato lato browser
