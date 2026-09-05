# Fields API

## Endpoint

- `GET /fields`
- `GET /fields?active=1`
- `POST /fields`
- `PUT /fields`
- `DELETE /fields`
- `PUT /fields-geocode`

## Auth

Richiede JWT.

## Permessi

- lettura: `fields.view`
- creazione: `fields.create`
- modifica: `fields.edit`
- eliminazione: `fields.delete`
- geocodifica: `fields.edit`

## Regole

- nome obbligatorio
- almeno una tipologia tra calcio a 11, 7 e 5
- cancellazione bloccata se lo stadio e usato da squadre o partite
- indirizzo strutturato con `address`, `city`, `province`, `postal_code`,
  `country`

## PUT /fields-geocode

Geocodifica l'indirizzo gia salvato dello stadio e aggiorna:

- `latitude`
- `longitude`
- `geocoded_at`

La chiamata e manuale e usa il provider configurato in `config/geocoding.php`.
La query di geocodifica viene costruita usando i campi indirizzo dello stadio.
