# User Management Workflow

1. Utente autorizzato accede alla pagina Utenti.
2. Frontend verifica `users.view`.
3. Frontend carica utenti e profili da `/api/v1/users`.
4. Se l'utente possiede `users.create`, puo aprire la modale nuovo utente.
5. Se l'utente possiede `users.edit`, puo aprire la modale modifica utente.
6. In creazione il salvataggio invia `POST /api/v1/users`.
7. In modifica il salvataggio invia `PUT /api/v1/users`.
8. Backend valida username, nome, cognome, email, profilo e password.
9. Backend salva i dati utente e aggiorna `users.profile_id`.
10. I permessi effettivi dell'utente cambiano al successivo refresh `/me` o al
   nuovo login.

## Eliminazione

1. Utente con permesso `users.delete` vede il pulsante Elimina nella griglia.
2. Frontend chiede conferma.
3. Backend blocca l'eliminazione del proprio utente.
4. Backend blocca l'eliminazione dell'ultimo amministratore.
5. Backend elimina l'utente tramite `DELETE /api/v1/users?id=...`.

## Regole

- la password e obbligatoria in creazione
- la password e opzionale in modifica
- la creazione richiede `users.create`
- la modifica richiede `users.edit`
- l'eliminazione richiede `users.delete`
- `users.role` non viene piu usato

## Smoke Test

Lo script `tests/smoke/users_smoke.php` verifica:

- creazione utente
- modifica dati anagrafici senza cambio password
- modifica password
- validazione username duplicato

Il test lavora dentro una transazione e termina con rollback.
