<?php
namespace Api\Services;

use Api\Core\Database;

class OperationalAlertService {
    private const DEFINITIONS = [
        'seasons_to_close' => [
            'label' => 'Stagioni da chiudere',
            'severity' => 'danger',
            'href' => 'seasons?alert=to_close',
            'required_permissions' => ['seasons.status.change']
        ],
        'matches_without_designation' => [
            'label' => 'Partite programmate senza arbitro',
            'severity' => 'warning',
            'href' => 'matches?alert=without_referee',
            'required_permissions' => ['designations.edit', 'matches.edit']
        ],
        'teams_without_field' => [
            'label' => 'Squadre senza stadio',
            'severity' => 'info',
            'href' => 'teams?alert=without_field',
            'required_permissions' => ['teams.edit']
        ],
        'referees_without_address' => [
            'label' => 'Arbitri senza indirizzo',
            'severity' => 'info',
            'href' => 'referees?alert=without_address',
            'required_permissions' => ['referees.edit']
        ],
        'fields_without_geocode' => [
            'label' => 'Stadi senza coordinate',
            'severity' => 'info',
            'href' => 'fields?alert=without_geocode',
            'required_permissions' => ['fields.edit']
        ],
        'competitions_without_teams' => [
            'label' => 'Competizioni senza squadre',
            'severity' => 'danger',
            'href' => 'competitions?alert=without_teams',
            'required_permissions' => ['competitions.edit', 'teams.edit']
        ]
    ];

    public static function forSeason(int $seasonId, bool $includePermissions = false): array
    {
        $db = Database::get();
        $counts = [
            'seasons_to_close' => self::scalar($db, "
                SELECT COUNT(*)
                FROM seasons
                WHERE status <> 'chiuso'
                  AND ends_on < CURDATE()
            "),
            'matches_without_designation' => self::scalar($db, "
                SELECT COUNT(*)
                FROM matches m
                LEFT JOIN designations d ON d.match_id = m.id AND d.referee_id IS NOT NULL
                WHERE m.season_id = ?
                  AND m.status = 'scheduled'
                  AND d.id IS NULL
            ", [$seasonId]),
            'teams_without_field' => self::scalar($db, "
                SELECT COUNT(DISTINCT t.id)
                FROM teams t
                JOIN competition_teams ct ON ct.team_id = t.id
                JOIN competitions c ON c.id = ct.competition_id
                WHERE c.season_id = ?
                  AND t.field_id IS NULL
            ", [$seasonId]),
            'referees_without_address' => self::scalar($db, "
                SELECT COUNT(*)
                FROM referees
                WHERE address IS NULL
                   OR TRIM(address) = ''
                   OR city IS NULL
                   OR TRIM(city) = ''
            "),
            'fields_without_geocode' => self::scalar($db, "
                SELECT COUNT(*)
                FROM fields
                WHERE latitude IS NULL
                   OR longitude IS NULL
            "),
            'competitions_without_teams' => self::scalar($db, "
                SELECT COUNT(*)
                FROM competitions c
                LEFT JOIN competition_teams ct ON ct.competition_id = c.id
                WHERE c.season_id = ?
                GROUP BY c.id
                HAVING COUNT(ct.team_id) = 0
            ", [$seasonId], true)
        ];

        $alerts = [];
        foreach (self::DEFINITIONS as $code => $definition) {
            $alert = [
                'code' => $code,
                'label' => $definition['label'],
                'count' => $counts[$code] ?? 0,
                'severity' => $definition['severity'],
                'href' => $definition['href']
            ];

            if ($includePermissions) {
                $alert['required_permissions'] = $definition['required_permissions'];
            }

            $alerts[] = $alert;
        }

        return $alerts;
    }

    public static function definitions(): array
    {
        return self::DEFINITIONS;
    }

    private static function scalar(\PDO $db, string $sql, array $params = [], bool $countRows = false): int
    {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($countRows) {
            return count($stmt->fetchAll());
        }

        return (int)$stmt->fetchColumn();
    }
}
