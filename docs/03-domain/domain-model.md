# Domain Model

## Entita Principali

- User
- Season
- Competition
- Team
- Referee
- Field
- Match
- Category
- Movement

## Relazioni Sintetiche

```text
Season 1 -> N Competition
Season 1 -> N Movement
Competition N -> N Team
Competition 1 -> N Match
Team 1 -> N Match come casa
Team 1 -> N Match come trasferta
Referee 1 -> N Match
Field 1 -> N Match
Category 1 -> N Movement
```
