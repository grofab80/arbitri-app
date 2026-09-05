# Fields / Stadi

Gli stadi sono anagrafiche usabili nelle partite.

## Campi Principali

- `id`
- `name`
- `address`
- `city`
- `province`
- `postal_code`
- `country`
- `latitude`
- `longitude`
- `geocoded_at`
- `can_host_11`
- `can_host_7`
- `can_host_5`
- `is_active`
- `notes`
- `created_at`

## Regole

- il nome e obbligatorio
- uno stadio deve poter ospitare almeno una tipologia tra calcio a 11, 7 e 5
- solo gli stadi attivi vengono proposti nella modale partita
- uno stadio non puo essere cancellato se e gia usato da una squadra o da una partita
- l'indirizzo ha la stessa struttura della residenza arbitro: via, citta,
  provincia, CAP e nazione
- le coordinate sono opzionali e valorizzate dalla geocodifica manuale

## Geocoding

La modale stadio consente di verificare manualmente l'indirizzo tramite
OpenStreetMap/Nominatim.

La geocodifica usa tutti i campi indirizzo disponibili. Le coordinate vengono
salvate nel database e sono usate per calcolare la distanza arbitro-stadio.
