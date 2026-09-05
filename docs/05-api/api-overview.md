# API Overview

Le API si trovano sotto `api/v1`.

## Formato

Request e response sono JSON.

## Autenticazione

Gli endpoint protetti richiedono JWT.

## Autorizzazione

Le route granulari usano `permissions`.

La chiave route `roles` puo restare temporaneamente come alias del codice
profilo, ma non dipende piu da `users.role`.

Il backend dispone di lettura permessi RBAC tramite `GET /me` e
`User::hasPermissions()`.

## Moduli Documentati

- Auth
- Balance
- Dashboard
- Notifications
- Football Sync API Contract
- WordPress Sync Import API
- Movements
- Seasons
- Competitions
- Teams
- Referees
- Fields
- Matches
- Permissions
- Users
