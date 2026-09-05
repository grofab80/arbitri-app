# Users Page

## Scopo

La pagina `admin/users` consente di visualizzare, creare, modificare ed
eliminare gli utenti applicativi.

## Permessi

- `users.view`: visualizzazione pagina e griglia
- `users.create`: creazione utenti
- `users.edit`: modifica utenti
- `users.delete`: eliminazione utenti

## Layout

- griglia utenti
- pulsante "Nuovo Utente"
- modale per creazione/modifica
- pulsante "Elimina" in griglia quando consentito

## Campi Modale

- username
- nome
- cognome
- email
- profilo RBAC
- password

In modifica la password e opzionale: se lasciata vuota non viene cambiata.

## Note

Il campo legacy `users.role` non viene piu mostrato.

La funzionalita di creazione/modifica e coperta dallo smoke test
`tests/smoke/users_smoke.php`.
