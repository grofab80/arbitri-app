# Designations API

## Endpoint

- `GET /designations`
- `GET /designations/summary`
- `PUT /designations`
- `POST /designations/generate`
- `POST /designations/regenerate`
- `POST /designations/confirm-filtered`
- `POST /designations/clear-automatic`
- `POST /designations/confirm`
- `GET /designation-blacklist`
- `POST /designation-blacklist`
- `DELETE /designation-blacklist`

## GET /designations

Restituisce le partite della stagione corrente con eventuale designazione
associata e la lista degli arbitri selezionabili per la disciplina filtrata.

### Query

- `football_type`: `11`, `7`, `5`
- `competition_id`: opzionale
- `match_day`: opzionale

### Risposta

- `matches`: partite con competizione, squadre, difficolta, stato designazione
  arbitro assegnato, score e dettagli score
- `referees`: arbitri abilitati alla disciplina richiesta

Ogni partita include inoltre:

- `availability_checked`: indica se il controllo disponibilita e stato
  calcolato sullo slot data/orario
- `available_referee_ids`: arbitri disponibili nello slot partita
- `unavailable_referee_ids`: arbitri non disponibili nello slot partita

### Autorizzazione

Richiede:

- `designations.view`

## GET /designations/summary

Restituisce KPI e arbitri piu utilizzati per i filtri designazioni.

### Query

- `football_type`: `11`, `7`, `5`
- `competition_id`: opzionale
- `match_day`: opzionale

### Risposta

- `total_matches`
- `to_designate`
- `proposed`
- `confirmed`
- `manual`
- `top_referees`

### Autorizzazione

Richiede:

- `designations.view`

## PUT /designations

Salva o aggiorna manualmente la designazione di una partita.

### Body

```json
{
  "match_id": 1,
  "referee_id": 2
}
```

`referee_id` puo essere `null` per rimuovere l'arbitro assegnato.

### Regole

- la partita deve esistere
- l'arbitro, se indicato, deve essere abilitato alla disciplina della
  competizione
- il salvataggio imposta `assignment_type = manual`
- il salvataggio imposta `status = modificata`
- anche `matches.referee_id` viene aggiornato per mantenere compatibili le
  viste esistenti sulle partite

### Autorizzazione

Richiede:

- `designations.edit`

## POST /designations/generate

Genera proposte automatiche per le partite filtrate.

### Body

```json
{
  "football_type": "11",
  "competition_id": 1,
  "match_day": 2
}
```

Tutti i filtri sono opzionali, ma da frontend viene sempre inviata la
disciplina.

### Regole

- considera solo la stagione corrente
- propone solo arbitri abilitati alla disciplina della competizione
- esclude arbitri non disponibili nello slot data/orario partita
- esclude arbitri in blacklist attiva con squadra casa o trasferta
- esclude arbitri gia designati nello stesso giorno entro una finestra minima
  di 120 minuti
- se la partita non ha ora, esclude arbitri gia designati nello stesso giorno
- se la partita non ha ora, il controllo disponibilita arbitro viene saltato
- esclude arbitri che avrebbero due partite consecutive con la stessa squadra
- non sovrascrive designazioni manuali o confermate
- aggiorna proposte assenti o gia automatiche
- salva `score` e `score_details_json`
- aggiorna anche `matches.referee_id`

### Score

Lo score parte da 100.

Penalita correnti:

- differenza tra rating arbitro e difficolta partita
- distanza tra residenza arbitro e stadio quando entrambe le coordinate sono
  disponibili
- carico stagionale dell'arbitro
- partite consecutive con la stessa squadra
- partite consecutive con la stessa squadra in casa

I pesi sono letti da `config/designations.php` e vengono salvati nei dettagli
score della proposta.

### Autorizzazione

Richiede:

- `designations.generate`

## POST /designations/regenerate

Rigenera le proposte automatiche per le partite filtrate non confermate.

### Regole

- non sovrascrive designazioni manuali
- non sovrascrive designazioni confermate
- aggiorna proposte automatiche esistenti
- crea proposte mancanti

### Autorizzazione

Richiede:

- `designations.generate`

## POST /designations/confirm-filtered

Conferma tutte le proposte filtrate con arbitro assegnato.

### Regole

- conferma solo righe con stato `proposta`
- ignora righe senza arbitro
- ignora designazioni manuali gia modificate
- ignora designazioni gia confermate

### Autorizzazione

Richiede:

- `designations.confirm`

## POST /designations/clear-automatic

Rimuove le proposte automatiche filtrate non confermate.

### Regole

- elimina solo designazioni con `assignment_type = auto`
- elimina solo designazioni con stato `proposta`
- imposta `matches.referee_id = NULL` quando punta alla proposta eliminata
- non tocca designazioni manuali
- non tocca designazioni confermate

### Autorizzazione

Richiede:

- `designations.generate`

## POST /designations/confirm

Conferma una designazione gia presente.

### Body

```json
{
  "match_id": 1
}
```

### Regole

- la designazione deve esistere
- la designazione deve avere un arbitro assegnato
- lo stato viene impostato a `confermata`
- una modifica manuale successiva resta possibile per utenti con
  `designations.edit`

### Autorizzazione

Richiede:

- `designations.confirm`

## GET /designation-blacklist

Restituisce le coppie arbitro/squadra attive in blacklist.

### Autorizzazione

Richiede:

- `designations.blacklist.manage`

## POST /designation-blacklist

Aggiunge o riattiva una coppia arbitro/squadra in blacklist.

### Body

```json
{
  "referee_id": 1,
  "team_id": 2,
  "reason": "Conflitto dichiarato"
}
```

### Regole

- arbitro obbligatorio
- squadra obbligatoria
- una coppia arbitro/squadra e univoca
- se una coppia disattivata esiste gia, viene riattivata

### Autorizzazione

Richiede:

- `designations.blacklist.manage`

## DELETE /designation-blacklist

Disattiva una regola blacklist.

### Query

- `id`

### Autorizzazione

Richiede:

- `designations.blacklist.manage`
