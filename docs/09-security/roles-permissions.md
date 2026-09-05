# Roles And Permissions

## Stato

Il progetto usa un RBAC leggero configurabile da database.

## Profili

Profili iniziali:

- `admin`: accesso completo
- `user`: accesso operativo limitato

Il profilo dell'utente e salvato in `users.profile_id`.

- `profiles`
- `permissions`
- `profile_permissions`

`admin` deve sempre avere tutti i permessi attivi, anche se una singola
associazione profilo/permesso manca.

Il precedente campo `users.role` e stato rimosso. La chiave route `roles` puo
restare temporaneamente nel router come compatibilita semantica, ma viene
valutata contro `profile_code`.

## Regole

- La sicurezza backend non deve dipendere dai controlli frontend.
- Le pagine sono filtrate lato frontend tramite permessi `*.view`.
- Le azioni dispositive saranno filtrate tramite permessi granulari
  `*.create`, `*.edit`, `*.delete` o simili.
- Le route possono usare `permissions` e, durante la transizione, mantenere
  anche `roles` come alias del codice profilo.

## Guard Frontend

Il frontend usa `/api/v1/me` per aggiornare i permessi e `Auth.can()` per
nascondere menu o bloccare accessi diretti alle pagine.

Questo controllo migliora l'esperienza utente, ma non sostituisce i controlli
backend.

## Controllo Backend

Il router supporta la chiave `permissions`.

Quando una route dichiara sia `roles` sia `permissions`, entrambi i controlli
devono essere superati. Questa scelta evita di ampliare accidentalmente i
permessi durante la migrazione.

## Notifiche Operative

Le notifiche operative sono filtrate anche lato backend.

- `admin` vede tutte le notifiche.
- Gli altri profili vedono solo notifiche che richiedono almeno uno dei
  permessi posseduti dall'utente.
- La topbar non e considerata un controllo di sicurezza: le pagine e gli
  endpoint di destinazione restano protetti dai rispettivi permessi.
