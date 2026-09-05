# Notifiche Operative

## Scopo

Le notifiche operative trasformano gli alert dashboard in eventi leggibili nella
topbar AdminLTE.

Un alert rappresenta lo stato corrente, ad esempio "5 partite senza arbitro".
Una notifica viene invece creata solo quando il valore dell'alert aumenta,
ad esempio da 4 a 5.

## Entita

### `operational_alert_snapshots`

Memorizza l'ultimo valore noto di ogni alert operativo.

Campi principali:

- `alert_code`: codice stabile dell'alert
- `count_value`: ultimo conteggio noto
- `last_checked_at`: data ultimo controllo

### `operational_notifications`

Contiene le notifiche generate quando un alert subisce un incremento.

Campi principali:

- `alert_code`: codice alert che ha generato la notifica
- `title`: titolo breve per dropdown topbar
- `message`: testo descrittivo
- `delta`: incremento rilevato
- `count_value`: nuovo valore totale dell'alert
- `href`: destinazione frontend, con eventuale filtro
- `required_permissions_json`: permessi richiesti per vedere la notifica

### `operational_notification_reads`

Memorizza lo stato di lettura per singolo utente.

Campi principali:

- `notification_id`
- `user_id`
- `read_at`
- `dismissed_at`

## Alert Supportati

Gli alert iniziali sono quelli gia esposti dalla dashboard:

- `seasons_to_close`
- `matches_without_designation`
- `teams_without_field`
- `referees_without_address`
- `fields_without_geocode`
- `competitions_without_teams`

## Regole

- Le notifiche vengono generate solo se `count corrente > count precedente`.
- Il primo controllo crea la baseline degli snapshot e non genera notifiche.
- Se un alert torna a zero non viene generata una notifica.
- Se un alert aumenta di piu unita, `delta` conserva l'incremento reale.
- La visibilita dipende dai permessi dell'utente.
- Il profilo `admin` vede sempre tutte le notifiche operative.
- Le notifiche devono portare alla stessa pagina/filtro dell'alert dashboard,
  quando disponibile.
- La sicurezza non deve dipendere dalla sola topbar: gli endpoint di
  destinazione restano protetti dai permessi backend.

## Mapping Permessi

Mapping previsto per la visibilita delle notifiche:

| Alert | Permessi |
| --- | --- |
| `seasons_to_close` | `seasons.status.change` |
| `matches_without_designation` | `designations.edit`, `matches.edit` |
| `teams_without_field` | `teams.edit` |
| `referees_without_address` | `referees.edit` |
| `fields_without_geocode` | `fields.edit` |
| `competitions_without_teams` | `competitions.edit`, `teams.edit` |

La semantica consigliata e OR: la notifica e visibile se l'utente possiede
almeno uno dei permessi richiesti per quell'alert.

## Stato Implementazione

Implementato in Fase 1:

- tabelle per snapshot alert, notifiche e letture utente
- documentazione dominio e mapping permessi

Implementato in Fase 2:

- service `OperationalAlertService` per calcolare gli alert operativi correnti
- service `OperationalNotificationService` per confrontare alert e snapshot
- generazione notifiche quando il conteggio corrente supera quello precedente
- dashboard aggiornata per leggere gli alert dal service dedicato

Implementato in Fase 3:

- endpoint `GET /api/v1/notifications`
- endpoint `PUT /api/v1/notifications-read`
- endpoint `PUT /api/v1/notifications-read-all`
- filtro backend delle notifiche in base ai permessi utente
- marcatura lettura per singolo utente

Implementato in Fase 4:

- dropdown notifiche nella topbar AdminLTE
- badge con numero notifiche non lette
- elenco notifiche cliccabile verso la pagina filtrata dell'alert
- azione "Segna tutte come lette"

Implementato in Fase 5:

- hook di sincronizzazione notifiche dopo azioni dispositive che possono
  modificare gli alert operativi
- sincronizzazione non bloccante: eventuali errori del modulo notifiche vengono
  loggati ma non annullano l'operazione principale
- controller coperti: stagioni, partite, designazioni, squadre, arbitri, stadi
  e competizioni

Implementato in Fase 6:

- smoke test `tests/smoke/operational_notifications_smoke.php`
- verifica baseline, incremento alert, filtro permessi e marcatura lettura

Non ancora implementato:

- polling o refresh periodico automatico della topbar
