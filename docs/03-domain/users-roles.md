# Users And Roles

## Utenti

Gli utenti sono salvati in `users`.

Campi principali:

- credenziali
- nome
- cognome
- email
- profilo RBAC obbligatorio
- dati anagrafici

## Profili

`profiles` e `users.profile_id` sono la fonte canonica della profilazione.

Il profilo e la base configurabile per la lettura dei permessi.

La pagina `admin/users` consente di creare utenti e modificare dati
anagrafici, password e profilo RBAC. Consente anche l'eliminazione quando il
profilo possiede il permesso dedicato.

Il precedente campo `users.role` e stato rimosso per evitare doppie fonti di
autorizzazione.

Permessi:

- `users.view`: visualizzazione utenti
- `users.create`: creazione utenti
- `users.edit`: modifica utenti
- `users.delete`: eliminazione utenti

Regole eliminazione:

- un utente non puo eliminare se stesso
- l'ultimo amministratore non puo essere eliminato

## Admin

Puo gestire:

- movimenti
- stagioni
- competizioni
- squadre
- arbitri
- anagrafiche

## Utente

Puo accedere alle funzioni operative abilitate.

Le restrizioni devono essere applicate sempre lato backend.

## Segreteria

Profilo dedicato alle funzioni amministrative ed economiche.

Permessi iniziali:

- visualizzazione dashboard
- visualizzazione movimenti
- creazione/modifica movimenti
- visualizzazione bilancio

Regole:

- non possiede permessi di eliminazione
- non gestisce utenti, permessi o configurazioni tecniche
- il bilancio viene dedotto dai movimenti della stagione corrente
