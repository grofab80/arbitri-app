# Referees API

## Endpoint

- `GET /referees`
- `GET /referee-candidates`
- `POST /referees`
- `PUT /referees`
- `DELETE /referees`
- `PUT /referees-geocode`
- `GET /referee-availabilities`
- `POST /referee-availabilities`
- `PUT /referee-availabilities`
- `DELETE /referee-availabilities`

## Regole

Un arbitro deve essere abilitato ad almeno una tipologia tra 11, 7 e 5.

Il rating deve essere un valore numerico tra 1 e 5.

La residenza e opzionale e puo includere:

- `address`
- `city`
- `province`
- `postal_code`
- `country`

Le coordinate `latitude`, `longitude` e `geocoded_at` sono restituite quando
presenti, ma non vengono ancora valorizzate automaticamente dalle API.

## PUT /referees-geocode

Geocodifica la residenza gia salvata dell'arbitro e aggiorna:

- `latitude`
- `longitude`
- `geocoded_at`

### Query

- `id`

### Autorizzazione

Richiede:

- `referees.edit`

### Note

Il provider configurato e OpenStreetMap/Nominatim.

La chiamata e manuale e non viene eseguita automaticamente al salvataggio.

## GET /referee-candidates

Restituisce gli arbitri utilizzabili nella selezione di una partita.

### Query

- `field_id` opzionale

### Risposta

Ogni arbitro include i campi dell'anagrafica e:

- `distance_km`: distanza indicativa dallo stadio, oppure `null` se mancano le
  coordinate di arbitro o stadio

### Ordinamento

Se `field_id` e valorizzato e le coordinate sono disponibili, gli arbitri con
distanza calcolata vengono ordinati per distanza crescente.

In assenza di distanza, o a parita di distanza, l'ordinamento usa:

1. rating decrescente
2. nome arbitro

### Autorizzazione

Richiede:

- `matches.view`

### Note

La distanza e calcolata sulle coordinate geografiche tramite formula Haversine.
Non rappresenta una distanza stradale.

## Disponibilita Arbitro

Le disponibilita sono gestite dagli endpoint `referee-availabilities`.

### GET /referee-availabilities

Restituisce le disponibilita registrate.

Query:

- `referee_id` opzionale

Autorizzazione:

- `referee_availabilities.view`

### POST /referee-availabilities

Crea una disponibilita.

Payload ricorrente:

```json
{
  "referee_id": 1,
  "type": "recurring",
  "weekday": 1,
  "start_time": "19:00",
  "end_time": "23:59",
  "is_available": 1,
  "notes": "Disponibile dopo lavoro"
}
```

Payload puntuale:

```json
{
  "referee_id": 1,
  "type": "specific",
  "available_date": "2026-09-15",
  "start_time": "20:00",
  "end_time": "22:30",
  "is_available": 0,
  "notes": "Indisponibile"
}
```

Autorizzazione:

- `referee_availabilities.create`

### PUT /referee-availabilities

Aggiorna una disponibilita esistente.

Query:

- `id`

Autorizzazione:

- `referee_availabilities.edit`

### DELETE /referee-availabilities

Elimina una disponibilita.

Query:

- `id`

Autorizzazione:

- `referee_availabilities.delete`

### Regole

- `type = recurring` richiede `weekday` da 1 a 7 e non accetta
  `available_date`
- `type = specific` richiede `available_date` e non accetta `weekday`
- `start_time` deve essere precedente a `end_time`
- per indicare fine giornata usare `23:59`
- il motore designazioni controlla prima le disponibilita puntuali della data,
  poi quelle ricorrenti del giorno settimana
- un arbitro senza disponibilita valida nello slot partita non viene proposto
  automaticamente
