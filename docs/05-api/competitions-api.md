# Competitions API

## Endpoint

- `GET /competitions`
- `POST /competitions`
- `PUT /competitions`
- `DELETE /competitions`
- `GET /competition-standings`
- `PUT /competition-standings`
- `PUT /competition-standings-recalculate`

## Regole

- nome obbligatorio
- stagione obbligatoria
- tipo valido
- calcio valido
- cancellazione bloccata se usata
- la classifica e prevista solo per competizioni di tipo `campionato`
- la gestione classifica richiede `competitions.standings.manage`

## GET /competition-standings

Restituisce la classifica della competizione.

### Query

- `competition_id`

### Autorizzazione

Richiede:

- `competitions.view`

### Regole

- disponibile solo per competizioni `campionato`
- inizializza eventuali righe mancanti per le squadre collegate alla
  competizione

## PUT /competition-standings

Aggiorna manualmente la classifica della competizione.

### Query

- `competition_id`

### Payload

```json
{
  "standings": [
    {
      "team_id": 1,
      "rank_position": 1,
      "played": 10,
      "won": 8,
      "drawn": 1,
      "lost": 1,
      "goals_for": 24,
      "goals_against": 8,
      "penalty_points": -3,
      "points": 25,
      "notes": ""
    }
  ]
}
```

### Autorizzazione

Richiede:

- `competitions.standings.manage`

### Regole

- disponibile solo per competizioni `campionato`
- le squadre devono appartenere alla competizione
- valori statistici non negativi
- `points` puo essere negativo per supportare eventuali penalizzazioni
- `penalty_points` puo essere negativo e rappresenta penalizzazioni come `-3`

## PUT /competition-standings-recalculate

Ricalcola la classifica dalle partite giocate della competizione.

### Query

- `competition_id`

### Autorizzazione

Richiede:

- `competitions.standings.manage`

### Regole

- disponibile solo per competizioni `campionato`
- considera solo partite non annullate
- considera solo partite con reti casa e trasferta valorizzate
- vittoria: 3 punti
- pareggio: 1 punto
- sconfitta: 0 punti
- ordina per punti, differenza reti, gol fatti, nome squadra
- mantiene le penalizzazioni esistenti e le applica ai punti finali
- mantiene le note esistenti
