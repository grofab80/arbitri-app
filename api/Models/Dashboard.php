<?php
namespace Api\Models;

use Api\Core\Database;
use Api\Services\OperationalAlertService;

class Dashboard {

    public static function kpi(): array
    {
        $season = Season::current();

        if (!$season) {
            return self::emptyKpi();
        }

        $seasonId = (int)$season['id'];
        $financial = self::financialKpi($seasonId);
        $sports = self::sportsKpi($seasonId);

        $teams = (int)$sports['teams'];
        $income = (float)$financial['income'];

        return [
            'season' => [
                'id' => $seasonId,
                'name' => $season['name']
            ],
            'income' => $income,
            'expenses' => (float)$financial['expenses'],
            'profit' => (float)$financial['profit'],
            'total_movements' => (int)$financial['total_movements'],
            'scheduled_matches' => (int)$sports['scheduled_matches'],
            'played_matches' => (int)$sports['played_matches'],
            'cancelled_matches' => (int)$sports['cancelled_matches'],
            'total_matches' => (int)$sports['total_matches'],
            'competitions' => (int)$sports['competitions'],
            'teams' => $teams,
            'referees' => (int)$sports['referees'],
            'avg_income_per_team' => $teams > 0 ? round($income / $teams, 2) : 0,
            'designation_summary' => self::designationSummary(),
            'upcoming_matches' => self::upcomingMatches($seasonId),
            'competition_balances' => self::competitionBalances($seasonId),
            'operational_alerts' => OperationalAlertService::forSeason($seasonId)
        ];
    }

    public static function charts(): array
    {
        $season = Season::current();

        if (!$season) {
            return [
                'monthly_financial' => self::emptyMonthlyFinancial(),
                'match_status' => self::emptyMatchStatus(),
                'financial_categories' => self::emptyFinancialCategories()
            ];
        }

        $seasonId = (int)$season['id'];

        return [
            'monthly_financial' => self::monthlyFinancial($seasonId, $season['starts_on'], $season['ends_on']),
            'match_status' => self::matchStatus($seasonId),
            'financial_categories' => self::financialCategories($seasonId)
        ];
    }

    private static function financialKpi(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0) AS expenses,
                COUNT(*) AS total_movements
            FROM movements m
            JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            WHERE m.season_id = ?
        ");
        $stmt->execute([$seasonId]);

        $row = $stmt->fetch() ?: [
            'income' => 0,
            'expenses' => 0,
            'total_movements' => 0
        ];

        $income = (float)$row['income'];
        $expenses = (float)$row['expenses'];

        return [
            'income' => $income,
            'expenses' => $expenses,
            'profit' => $income - $expenses,
            'total_movements' => (int)$row['total_movements']
        ];
    }

    private static function sportsKpi(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                (SELECT COUNT(*) FROM matches WHERE season_id = ? AND status = 'scheduled') AS scheduled_matches,
                (SELECT COUNT(*) FROM matches WHERE season_id = ? AND status = 'played') AS played_matches,
                (SELECT COUNT(*) FROM matches WHERE season_id = ? AND status = 'cancelled') AS cancelled_matches,
                (SELECT COUNT(*) FROM matches WHERE season_id = ?) AS total_matches,
                (SELECT COUNT(*) FROM competitions WHERE season_id = ?) AS competitions,
                (
                    SELECT COUNT(DISTINCT ct.team_id)
                    FROM competition_teams ct
                    JOIN competitions c ON c.id = ct.competition_id
                    WHERE c.season_id = ?
                ) AS teams,
                (SELECT COUNT(*) FROM referees) AS referees
        ");
        $stmt->execute([$seasonId, $seasonId, $seasonId, $seasonId, $seasonId, $seasonId]);

        return $stmt->fetch() ?: [
            'scheduled_matches' => 0,
            'played_matches' => 0,
            'cancelled_matches' => 0,
            'total_matches' => 0,
            'competitions' => 0,
            'teams' => 0,
            'referees' => 0
        ];
    }

    private static function monthlyFinancial(int $seasonId, string $startsOn, string $endsOn): array
    {
        $months = self::monthsBetween($startsOn, $endsOn);
        $stmt = Database::get()->prepare("
            SELECT
                DATE_FORMAT(m.movement_date, '%Y-%m') AS month_key,
                COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0) AS expenses
            FROM movements m
            JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            WHERE m.season_id = ?
            GROUP BY DATE_FORMAT(m.movement_date, '%Y-%m')
        ");
        $stmt->execute([$seasonId]);

        $values = [];
        foreach ($stmt->fetchAll() as $row) {
            $values[$row['month_key']] = [
                'income' => (float)$row['income'],
                'expenses' => (float)$row['expenses']
            ];
        }

        $labels = [];
        $income = [];
        $expenses = [];
        $profit = [];

        foreach ($months as $key => $label) {
            $monthIncome = $values[$key]['income'] ?? 0;
            $monthExpenses = $values[$key]['expenses'] ?? 0;

            $labels[] = $label;
            $income[] = $monthIncome;
            $expenses[] = $monthExpenses;
            $profit[] = $monthIncome - $monthExpenses;
        }

        return [
            'labels' => $labels,
            'income' => $income,
            'expenses' => $expenses,
            'profit' => $profit
        ];
    }

    private static function matchStatus(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT status, COUNT(*) AS total
            FROM matches
            WHERE season_id = ?
            GROUP BY status
        ");
        $stmt->execute([$seasonId]);

        $values = [
            'scheduled' => 0,
            'played' => 0,
            'cancelled' => 0
        ];

        foreach ($stmt->fetchAll() as $row) {
            $values[$row['status']] = (int)$row['total'];
        }

        return [
            'labels' => ['Programmate', 'Giocate', 'Annullate'],
            'values' => [
                $values['scheduled'],
                $values['played'],
                $values['cancelled']
            ]
        ];
    }

    private static function financialCategories(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                c1.name AS type_name,
                c2.name AS category_name,
                COALESCE(SUM(m.amount), 0) AS total
            FROM movements m
            JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            WHERE m.season_id = ?
              AND c1.name IN ('Entrate', 'Uscite')
            GROUP BY c1.name, c2.name
            ORDER BY c1.name, total DESC, c2.name
        ");
        $stmt->execute([$seasonId]);

        $groups = [
            'income' => [
                'labels' => [],
                'values' => []
            ],
            'expenses' => [
                'labels' => [],
                'values' => []
            ]
        ];

        foreach ($stmt->fetchAll() as $row) {
            $key = $row['type_name'] === 'Entrate' ? 'income' : 'expenses';

            if (count($groups[$key]['labels']) >= 6) {
                continue;
            }

            $groups[$key]['labels'][] = $row['category_name'] ?: 'Non classificato';
            $groups[$key]['values'][] = (float)$row['total'];
        }

        return $groups;
    }

    private static function designationSummary(): array
    {
        $summary = Designation::summary();

        return [
            'total_matches' => (int)($summary['total_matches'] ?? 0),
            'to_designate' => (int)($summary['to_designate'] ?? 0),
            'proposed' => (int)($summary['proposed'] ?? 0),
            'confirmed' => (int)($summary['confirmed'] ?? 0),
            'manual' => (int)($summary['manual'] ?? 0),
            'top_referees' => array_map(static fn (array $row): array => [
                'referee_id' => (int)($row['referee_id'] ?? 0),
                'referee_name' => $row['referee_name'] ?? 'Arbitro',
                'total' => (int)($row['total'] ?? 0),
                'confirmed' => (int)($row['confirmed'] ?? 0),
                'proposed' => (int)($row['proposed'] ?? 0),
                'manual' => (int)($row['manual'] ?? 0)
            ], $summary['top_referees'] ?? [])
        ];
    }

    private static function upcomingMatches(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                m.id,
                m.match_day,
                m.match_date,
                m.match_time,
                c.name AS competition_name,
                ht.name AS home_team_name,
                at.name AS away_team_name,
                r.name AS referee_name,
                d.status AS designation_status
            FROM matches m
            JOIN competitions c ON c.id = m.competition_id
            JOIN teams ht ON ht.id = m.home_team_id
            JOIN teams at ON at.id = m.away_team_id
            LEFT JOIN designations d ON d.match_id = m.id
            LEFT JOIN referees r ON r.id = d.referee_id
            WHERE m.season_id = ?
              AND m.status = 'scheduled'
            ORDER BY m.match_date, m.match_time, c.name, m.match_day
            LIMIT 5
        ");
        $stmt->execute([$seasonId]);

        return $stmt->fetchAll();
    }

    private static function competitionBalances(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                summary.*
            FROM (
                SELECT
                    c.id AS competition_id,
                    c.name AS competition_name,
                    COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0) AS expenses,
                    COUNT(m.id) AS movements
                FROM competitions c
                LEFT JOIN movements m ON m.competition_id = c.id
                LEFT JOIN categories c4 ON c4.id = m.category_id
                LEFT JOIN categories c3 ON c3.id = c4.parent_id
                LEFT JOIN categories c2 ON c2.id = c3.parent_id
                LEFT JOIN categories c1 ON c1.id = c2.parent_id
                WHERE c.season_id = ?
                GROUP BY c.id, c.name
                HAVING movements > 0
            ) summary
            ORDER BY ABS(summary.income - summary.expenses) DESC, summary.competition_name
            LIMIT 6
        ");
        $stmt->execute([$seasonId]);

        return array_map(static function (array $row): array {
            $income = (float)$row['income'];
            $expenses = (float)$row['expenses'];

            return [
                'competition_id' => (int)$row['competition_id'],
                'competition_name' => $row['competition_name'],
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $income - $expenses,
                'movements' => (int)$row['movements']
            ];
        }, $stmt->fetchAll());
    }

    private static function monthsBetween(string $startsOn, string $endsOn): array
    {
        $months = [];
        $current = new \DateTime(date('Y-m-01', strtotime($startsOn)));
        $end = new \DateTime(date('Y-m-01', strtotime($endsOn)));

        while ($current <= $end) {
            $key = $current->format('Y-m');
            $months[$key] = $current->format('m/Y');
            $current->modify('+1 month');
        }

        return $months;
    }

    private static function emptyKpi(): array
    {
        return [
            'season' => null,
            'income' => 0,
            'expenses' => 0,
            'profit' => 0,
            'total_movements' => 0,
            'scheduled_matches' => 0,
            'played_matches' => 0,
            'cancelled_matches' => 0,
            'total_matches' => 0,
            'competitions' => 0,
            'teams' => 0,
            'referees' => 0,
            'avg_income_per_team' => 0,
            'designation_summary' => [
                'total_matches' => 0,
                'to_designate' => 0,
                'proposed' => 0,
                'confirmed' => 0,
                'manual' => 0,
                'top_referees' => []
            ],
            'upcoming_matches' => [],
            'competition_balances' => [],
            'operational_alerts' => []
        ];
    }

    private static function emptyMonthlyFinancial(): array
    {
        return [
            'labels' => [],
            'income' => [],
            'expenses' => [],
            'profit' => []
        ];
    }

    private static function emptyMatchStatus(): array
    {
        return [
            'labels' => ['Programmate', 'Giocate', 'Annullate'],
            'values' => [0, 0, 0]
        ];
    }

    private static function emptyFinancialCategories(): array
    {
        return [
            'income' => [
                'labels' => [],
                'values' => []
            ],
            'expenses' => [
                'labels' => [],
                'values' => []
            ]
        ];
    }
}
