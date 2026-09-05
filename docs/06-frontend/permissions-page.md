# Permissions Page

## Scopo

La pagina `admin/permissions` consente di visualizzare e configurare i permessi
assegnati ai profili.

## Visibilita

La pagina e visibile con:

- `permissions.view`

Il salvataggio e disponibile con:

- `permissions.manage`

## Layout

La pagina e composta da:

- lista profili a sinistra
- matrice permessi a destra
- permessi raggruppati per scope:
  - Pagine
  - Azioni
  - Sistema

## Regole UI

- Il profilo `admin` viene mostrato con tutti i permessi selezionati.
- Il profilo `admin` non e modificabile.
- Se l'utente ha solo `permissions.view`, la pagina e in sola lettura.
- Il salvataggio aggiorna l'intero set di permessi del profilo selezionato.
- Le checkbox dei permessi `*.delete` sono disabilitate sui profili non admin.
- I permessi di eliminazione mostrano nota `(solo admin)`.

## File

- `admin/permissions.php`
- `admin/js/permissions.js`
