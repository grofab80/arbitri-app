# Constraints And Indexes

## Vincoli

Usare FK per preservare integrita tra entita.

## Indici

Indici consigliati:

- `movements.season_id`
- `movements.category_id`
- `movements.movement_date`
- `movements.competition_id`
- `movements.team_id`
- `matches.season_id`
- `matches.competition_id`
- `matches.match_date`
- `matches.field_id`
- `designations.match_id`
- `designations.referee_id`
- `designations.status`
- `referee_team_blacklist.referee_id`
- `referee_team_blacklist.team_id`
- `referee_team_blacklist.active`
- `competition_standings.competition_id`
- `competition_standings.team_id`
- `competition_teams.competition_id`
- `competition_teams.team_id`
- `teams.field_id`
- `balance_closures.season_id`
- `balance_closures.approval_status`
- `balance_closures.approved_by`
