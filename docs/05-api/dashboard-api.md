# Dashboard API

## GET /dashboard-kpi

Restituisce KPI aggregati della stagione corrente.

### Auth

Richiede JWT.

### Response

```json
{
  "success": true,
  "data": {
    "season": {
      "id": 1,
      "name": "2025/2026"
    },
    "income": 0,
    "expenses": 0,
    "profit": 0,
    "total_movements": 0,
    "scheduled_matches": 0,
    "played_matches": 0,
    "cancelled_matches": 0,
    "total_matches": 0,
    "competitions": 0,
    "teams": 0,
    "referees": 0,
    "avg_income_per_team": 0,
    "designation_summary": {
      "total_matches": 0,
      "to_designate": 0,
      "proposed": 0,
      "confirmed": 0,
      "manual": 0,
      "top_referees": [
        {
          "referee_id": 1,
          "referee_name": "Mario Rossi",
          "total": 3,
          "confirmed": 2,
          "proposed": 1,
          "manual": 0
        }
      ]
    },
    "upcoming_matches": [],
    "competition_balances": [],
    "operational_alerts": [
      {
        "code": "matches_without_designation",
        "label": "Partite programmate senza arbitro",
        "count": 0,
        "severity": "warning",
        "href": "matches?alert=without_referee"
      }
    ]
  }
}
```

### Operational Alerts

Gli alert operativi sono sempre restituiti, anche con `count = 0`.

Alert previsti:

- `seasons_to_close`
- `matches_without_designation`
- `teams_without_field`
- `referees_without_address`
- `fields_without_geocode`
- `competitions_without_teams`

Il campo `href`, quando presente, punta alla pagina piu coerente con un
parametro `alert` che il frontend usa per applicare un filtro client-side.

Gli alert sono anche la sorgente del sistema di notifiche operative: il service
dedicato genera una notifica solo quando il conteggio di un alert aumenta
rispetto allo snapshot precedente. La dashboard continua a restituire solo lo
stato corrente degli alert.

## GET /dashboard-charts

Restituisce dataset per i grafici dashboard.

### Auth

Richiede JWT.

### Dataset

- `monthly_financial`: entrate, uscite e utile per mese
- `financial_categories`: entrate e uscite aggregate per macro-categoria
