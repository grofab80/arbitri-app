<?php
namespace Api\Models;

use Api\Core\Database;

class CompetitionStanding {

    public static function syncTeams(int $competitionId): void
    {
        $stmt = Database::get()->prepare(
            "
            INSERT IGNORE INTO competition_standings (competition_id, team_id)
            SELECT competition_id, team_id
            FROM competition_teams
            WHERE competition_id = ?
            "
        );
        $stmt->execute([$competitionId]);
    }

    public static function allForCompetition(int $competitionId): array
    {
        self::syncTeams($competitionId);

        $stmt = Database::get()->prepare(
            "
            SELECT
                cs.id,
                cs.competition_id,
                cs.team_id,
                t.name AS team_name,
                cs.rank_position,
                cs.played,
                cs.won,
                cs.drawn,
                cs.lost,
                cs.goals_for,
                cs.goals_against,
                (CAST(cs.goals_for AS SIGNED) - CAST(cs.goals_against AS SIGNED)) AS goal_difference,
                cs.penalty_points,
                cs.points,
                cs.notes,
                cs.updated_at,
                cs.created_at
            FROM competition_standings cs
            INNER JOIN teams t ON t.id = cs.team_id
            WHERE cs.competition_id = ?
            ORDER BY
                cs.rank_position IS NULL,
                cs.rank_position,
                cs.points DESC,
                goal_difference DESC,
                cs.goals_for DESC,
                t.name
            "
        );
        $stmt->execute([$competitionId]);

        return $stmt->fetchAll();
    }

    public static function teamIdsForCompetition(int $competitionId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT team_id FROM competition_teams WHERE competition_id = ?"
        );
        $stmt->execute([$competitionId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'team_id'));
    }

    public static function updateMany(int $competitionId, array $standings): bool
    {
        self::syncTeams($competitionId);

        $db = Database::get();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                "
                UPDATE competition_standings SET
                    rank_position = ?,
                    played = ?,
                    won = ?,
                    drawn = ?,
                    lost = ?,
                    goals_for = ?,
                    goals_against = ?,
                    penalty_points = ?,
                    points = ?,
                    notes = ?
                WHERE competition_id = ?
                  AND team_id = ?
                "
            );

            foreach ($standings as $row) {
                $stmt->execute([
                    self::nullablePositiveInt($row['rank_position'] ?? null),
                    (int)($row['played'] ?? 0),
                    (int)($row['won'] ?? 0),
                    (int)($row['drawn'] ?? 0),
                    (int)($row['lost'] ?? 0),
                    (int)($row['goals_for'] ?? 0),
                    (int)($row['goals_against'] ?? 0),
                    (int)($row['penalty_points'] ?? 0),
                    (int)($row['points'] ?? 0),
                    self::nullableString($row['notes'] ?? null),
                    $competitionId,
                    (int)$row['team_id']
                ]);
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function recalculateFromMatches(int $competitionId): bool
    {
        self::syncTeams($competitionId);

        $teams = self::teamsForCompetition($competitionId);
        $penalties = self::penaltiesForCompetition($competitionId);
        $stats = [];

        foreach ($teams as $team) {
            $teamId = (int)$team['id'];
            $stats[$teamId] = [
                'team_id' => $teamId,
                'team_name' => $team['name'],
                'rank_position' => null,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'penalty_points' => (int)($penalties[$teamId] ?? 0),
                'points' => 0
            ];
        }

        $stmt = Database::get()->prepare(
            "
            SELECT home_team_id, away_team_id, home_goals, away_goals
            FROM matches
            WHERE competition_id = ?
              AND status <> 'cancelled'
              AND home_goals IS NOT NULL
              AND away_goals IS NOT NULL
            "
        );
        $stmt->execute([$competitionId]);

        foreach ($stmt->fetchAll() as $match) {
            $homeTeamId = (int)$match['home_team_id'];
            $awayTeamId = (int)$match['away_team_id'];
            $homeGoals = (int)$match['home_goals'];
            $awayGoals = (int)$match['away_goals'];

            if (!isset($stats[$homeTeamId], $stats[$awayTeamId])) {
                continue;
            }

            $stats[$homeTeamId]['played']++;
            $stats[$awayTeamId]['played']++;

            $stats[$homeTeamId]['goals_for'] += $homeGoals;
            $stats[$homeTeamId]['goals_against'] += $awayGoals;
            $stats[$awayTeamId]['goals_for'] += $awayGoals;
            $stats[$awayTeamId]['goals_against'] += $homeGoals;

            if ($homeGoals > $awayGoals) {
                $stats[$homeTeamId]['won']++;
                $stats[$awayTeamId]['lost']++;
                $stats[$homeTeamId]['points'] += 3;
            } elseif ($homeGoals < $awayGoals) {
                $stats[$awayTeamId]['won']++;
                $stats[$homeTeamId]['lost']++;
                $stats[$awayTeamId]['points'] += 3;
            } else {
                $stats[$homeTeamId]['drawn']++;
                $stats[$awayTeamId]['drawn']++;
                $stats[$homeTeamId]['points']++;
                $stats[$awayTeamId]['points']++;
            }
        }

        $standings = array_values($stats);

        foreach ($standings as &$row) {
            $row['points'] += (int)$row['penalty_points'];
        }
        unset($row);

        usort($standings, function ($a, $b) {
            $goalDifferenceA = $a['goals_for'] - $a['goals_against'];
            $goalDifferenceB = $b['goals_for'] - $b['goals_against'];

            return ($b['points'] <=> $a['points'])
                ?: ($goalDifferenceB <=> $goalDifferenceA)
                ?: ($b['goals_for'] <=> $a['goals_for'])
                ?: strcmp((string)$a['team_name'], (string)$b['team_name']);
        });

        foreach ($standings as $index => &$row) {
            $row['rank_position'] = $index + 1;
        }
        unset($row);

        return self::updateCalculatedRows($competitionId, $standings);
    }

    private static function teamsForCompetition(int $competitionId): array
    {
        $stmt = Database::get()->prepare(
            "
            SELECT t.id, t.name
            FROM competition_teams ct
            INNER JOIN teams t ON t.id = ct.team_id
            WHERE ct.competition_id = ?
            ORDER BY t.name
            "
        );
        $stmt->execute([$competitionId]);

        return $stmt->fetchAll();
    }

    private static function penaltiesForCompetition(int $competitionId): array
    {
        $stmt = Database::get()->prepare(
            "
            SELECT team_id, penalty_points
            FROM competition_standings
            WHERE competition_id = ?
            "
        );
        $stmt->execute([$competitionId]);

        $penalties = [];
        foreach ($stmt->fetchAll() as $row) {
            $penalties[(int)$row['team_id']] = (int)($row['penalty_points'] ?? 0);
        }

        return $penalties;
    }

    private static function updateCalculatedRows(int $competitionId, array $standings): bool
    {
        $db = Database::get();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                "
                UPDATE competition_standings SET
                    rank_position = ?,
                    played = ?,
                    won = ?,
                    drawn = ?,
                    lost = ?,
                    goals_for = ?,
                    goals_against = ?,
                    penalty_points = ?,
                    points = ?
                WHERE competition_id = ?
                  AND team_id = ?
                "
            );

            foreach ($standings as $row) {
                $stmt->execute([
                    (int)$row['rank_position'],
                    (int)$row['played'],
                    (int)$row['won'],
                    (int)$row['drawn'],
                    (int)$row['lost'],
                    (int)$row['goals_for'],
                    (int)$row['goals_against'],
                    (int)$row['penalty_points'],
                    (int)$row['points'],
                    $competitionId,
                    (int)$row['team_id']
                ]);
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function nullablePositiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }

    private static function nullableString($value): ?string
    {
        $value = trim((string)($value ?? ''));

        return $value === '' ? null : $value;
    }
}
