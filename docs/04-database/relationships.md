# Relationships

```text
seasons.id -> competitions.season_id
seasons.id -> matches.season_id
seasons.id -> movements.season_id

competitions.id -> competition_teams.competition_id
teams.id -> competition_teams.team_id
competitions.id -> competition_standings.competition_id
teams.id -> competition_standings.team_id

competitions.id -> matches.competition_id
teams.id -> matches.home_team_id
teams.id -> matches.away_team_id
referees.id -> matches.referee_id
fields.id -> matches.field_id
fields.id -> teams.field_id

categories.id -> movements.category_id
competitions.id -> movements.competition_id
teams.id -> movements.team_id
referees.id -> movements.referee_id
```
