# Frontend Overview

Il frontend admin usa pagine PHP con AdminLTE e JavaScript dedicato.

## Librerie

- AdminLTE
- Bootstrap
- Font Awesome
- jQuery
- DataTables
- jquery-toast-plugin

## Regole

- una pagina dinamica deve avere un JS dedicato
- le griglie devono usare DataTables
- i messaggi devono usare toast in basso a sinistra
- le operazioni CRUD devono usare modali Bootstrap
- la visibilita delle pagine in sidebar deve usare `data-permission`
- `Auth.can()` e il riferimento frontend per abilitare viste e azioni
- i pulsanti di creazione devono dichiarare `data-permission="modulo.create"`
- le azioni in griglia devono essere renderizzate solo quando il relativo
  permesso `*.edit`, `*.delete` o equivalente e disponibile

## Guard Pagine

`admin/js/auth.js` associa le pagine principali ai permessi `*.view`.

Se un utente autenticato accede direttamente a una pagina non consentita, viene
reindirizzato alla prima pagina disponibile o a `forbidden`.

## Azioni

Le pagine CRUD non devono basare i comandi dispositivi sul solo ruolo.

Pattern atteso:

```js
const canCreate = Auth.can('teams.create');
const canEdit = Auth.can('teams.edit');
const canDelete = Auth.can('teams.delete');
```

La colonna azioni deve mostrare `-` quando nessun comando e consentito.

## Pagine CRUD Attuali

- stagioni
- competizioni
- squadre
- arbitri
- stadi
- partite
- movimenti
- permessi
- utenti
- sincronizzazione WordPress
