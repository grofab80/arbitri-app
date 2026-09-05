# Permissions

## Scopo

Il modulo permessi introduce un RBAC leggero configurabile da database.

L'obiettivo e separare:

- profilo utente
- visibilita delle pagine
- azioni dispositive

## Entita

### `profiles`

Rappresenta un profilo applicativo assegnabile agli utenti.

Profili iniziali:

- `admin`
- `user`
- `segreteria`

### `permissions`

Catalogo stabile dei permessi applicativi.

Campi principali:

- `code`
- `description`
- `scope`
- `active`

Scope disponibili:

- `page`: abilita la visualizzazione di una pagina/sidebar item
- `action`: abilita una specifica azione dispositiva
- `system`: abilita funzioni tecniche, ad esempio `/me`

### `profile_permissions`

Relazione molti-a-molti tra profili e permessi.

## Regole

- `admin` deve avere sempre accesso a tutti i permessi attivi.
- Il codice permesso e un contratto stabile tra backend, frontend e documentazione.
- La disattivazione di un permesso impedira autorizzazioni future quando le route useranno il controllo RBAC.
- `users.profile_id` e il riferimento configurabile per i permessi.
- `users.role` e stato rimosso per evitare doppie fonti di autorizzazione.
- I permessi con codice `*.delete` sono riservati al profilo `admin`.
- Nessun profilo diverso da `admin` puo ricevere permessi di eliminazione,
  anche se l'utente che configura i permessi possiede `permissions.manage`.

## Permessi Iniziali

Pagine:

- `dashboard.view`
- `movements.view`
- `balance.view`
- `matches.view`
- `seasons.view`
- `competitions.view`
- `teams.view`
- `referees.view`
- `fields.view`
- `designations.view`
- `import.view`
- `settings.view`
- `permissions.view`
- `users.view`

Azioni:

- `movements.create`
- `movements.edit`
- `movements.delete`
- `balance.export`
- `balance.approve`
- `balance.archive`
- `designations.generate`
- `designations.edit`
- `designations.confirm`
- `designations.blacklist.manage`
- `matches.create`
- `matches.edit`
- `matches.delete`
- `seasons.create`
- `seasons.status.change`
- `competitions.create`
- `competitions.edit`
- `competitions.delete`
- `competitions.standings.manage`
- `teams.create`
- `teams.edit`
- `teams.delete`
- `referees.create`
- `referees.edit`
- `referees.delete`
- `referee_availabilities.view`
- `referee_availabilities.create`
- `referee_availabilities.edit`
- `referee_availabilities.delete`
- `fields.create`
- `fields.edit`
- `fields.delete`
- `settings.manage`
- `import.manage`
- `import.run`
- `permissions.manage`
- `users.create`
- `users.edit`
- `users.delete`

Sistema:

- `auth.me`

## Stato Implementazione

Implementato per il modulo Segreteria:

- profilo `segreteria`
- permesso pagina `balance.view`
- permesso azione futuro `balance.export`
- permesso azione `balance.approve` per approvare snapshot di chiusura
- permesso azione `balance.archive` per storicizzare bilanci approvati
- voce sidebar `Segreteria`
- pagina template `admin/balance`

Implementato per il modulo Designazioni:

- permesso pagina `designations.view`
- permesso azione `designations.generate`
- permesso azione `designations.edit`
- permesso azione `designations.confirm`
- permesso azione `designations.blacklist.manage`
- profilo `user` abilitato alla visualizzazione, generazione, modifica e
  conferma designazioni
- profilo `admin` abilitato anche alla gestione blacklist

Implementato in Fase 1:

- migration RBAC iniziale
- tabelle `profiles`, `permissions`, `profile_permissions`
- collegamento opzionale `users.profile_id`
- seed dei profili e dei permessi iniziali
- lettura permessi tramite `User::permissions()`
- verifica permessi tramite `User::hasPermissions()`
- middleware `JwtMiddleware::authorizePermissions()`
- endpoint `GET /api/v1/me`
- permessi inclusi nel JWT come bootstrap informativo

Implementato in Fase 2:

- refresh frontend dei permessi tramite `/me`
- helper frontend `Auth.can()`
- mapping pagina/permesso in `admin/js/auth.js`
- filtro sidebar tramite attributi `data-permission`
- guard frontend sugli accessi diretti alle pagine
- pagina `/admin/forbidden`

Implementato in Fase 3:

- supporto router alla chiave `permissions`
- dichiarazione dei permessi sulle route applicative
- verifica backend tramite `JwtMiddleware::authorizePermissions()`
- transizione prudente con controllo combinato `roles` + `permissions` dove
  entrambi sono presenti

Implementato in Fase 4:

- pulsanti "Nuovo" filtrati con `data-permission`
- azioni frontend di creazione, modifica, eliminazione e cambio stato basate su
  `Auth.can()`
- griglie con colonna azioni valorizzata solo in presenza del relativo permesso
- rimozione dei controlli CRUD basati direttamente su `Auth.isAdmin()`

Implementato in Fase 5:

- endpoint di lettura catalogo permessi e assegnazioni
- endpoint di aggiornamento permessi profilo
- blocco backend dei permessi `*.delete` sui profili non admin
- pagina admin `permissions`
- matrice permessi per profilo
- profilo `admin` visibile ma non modificabile
- checkbox `*.delete` disabilitate sui profili non admin

Implementato in Fase 6:

- permesso pagina `users.view`
- pagina admin `users`
- endpoint di lettura utenti/profili
- endpoint di assegnazione profilo RBAC a utente
- endpoint di creazione/modifica utente
- endpoint di eliminazione utente
- modale frontend per gestione dati utente, password e profilo
- pulsante nuovo utente filtrato da `users.create`
- pulsante modifica utente filtrato da `users.edit`
- pulsante elimina utente filtrato da `users.delete`

Non ancora implementato:

- gestione CRUD completa dei profili
