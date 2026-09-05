# Referees

Gli arbitri sono anagrafiche designabili sulle partite.

## Campi Principali

- `id`
- `name`
- `rating`
- `address`
- `city`
- `province`
- `postal_code`
- `country`
- `latitude`
- `longitude`
- `geocoded_at`
- `can_referee_11`
- `can_referee_7`
- `can_referee_5`
- `created_at`

## Disponibilita

Le disponibilita dell'arbitro sono gestite nella tabella
`referee_availabilities`.

Campi principali:

- `referee_id`
- `type`: `recurring` oppure `specific`
- `weekday`: giorno settimana ISO da 1 a 7, usato solo per disponibilita
  ricorrenti
- `available_date`: data specifica, usata solo per disponibilita puntuali
- `start_time`
- `end_time`
- `is_available`: 1 disponibile, 0 non disponibile
- `notes`

Regole:

- una disponibilita ricorrente usa `weekday` e non usa `available_date`
- una disponibilita puntuale usa `available_date` e non usa `weekday`
- `start_time` deve essere precedente a `end_time`
- un orario fino alle 24:00 viene salvato come `23:59`, per compatibilita con
  il tipo SQL `TIME`
- le disponibilita puntuali sono pensate anche per gestire eccezioni manuali
  rispetto alla disponibilita settimanale

## Regole

- almeno una abilitazione tra 11, 7 e 5 deve essere attiva
- il rating e manuale e va da 1 a 5
- il rating di default e 3
- la residenza e opzionale
- le coordinate sono opzionali e valorizzate dalla geocodifica manuale
- l'arbitro puo essere assegnato opzionalmente a una partita
- se viene cancellato, sulle partite collegate il riferimento viene portato a `NULL`

## Uso Futuro

Il rating viene usato come ordinamento secondario nella selezione arbitro.

La residenza viene usata per calcolare la distanza indicativa tra arbitro e
stadio quando entrambi hanno coordinate geografiche.

Provider geocoding previsto:

- OpenStreetMap/Nominatim o servizio compatibile OSM

Il salvataggio arbitro non chiama servizi esterni: indirizzo e coordinate sono
campi separati per permettere una futura geocodifica controllata e cache delle
coordinate.

La geocodifica e disponibile tramite azione manuale sulla modale arbitro.

## Uso Nelle Partite

Nella selezione arbitro della modale partita, gli arbitri sono mostrati con il
rating a stelle.

Se e selezionato uno stadio geocodificato e l'arbitro ha coordinate di
residenza, la lista mostra anche la distanza in km e viene ordinata per:

1. distanza crescente
2. rating decrescente
3. nome arbitro
