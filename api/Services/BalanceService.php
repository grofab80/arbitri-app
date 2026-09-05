<?php
namespace Api\Services;

use Api\Core\Database;
use Api\Models\Season;

class BalanceService {
    private const SNAPSHOT_VERSION = 1;

    public static function summary(array $filters = []): array
    {
        $season = Season::current();

        if (!$season) {
            return self::emptySummary();
        }

        return self::summaryForSeasonData($season, $filters);
    }

    public static function summaryForSeason(int $seasonId): array
    {
        $season = Season::find($seasonId);

        if (!$season) {
            return self::emptySummary();
        }

        return self::summaryForSeasonData($season, []);
    }

    public static function closeSeason(int $seasonId): ?array
    {
        $existingClosure = self::closureForSeason($seasonId);
        if (($existingClosure['approval_status'] ?? null) === 'approved') {
            return $existingClosure;
        }

        $summary = self::summaryForSeason($seasonId);

        if (empty($summary['season'])) {
            return null;
        }

        $snapshotPayload = self::closureSnapshot($summary);
        $snapshot = json_encode($snapshotPayload, JSON_UNESCAPED_UNICODE);
        $snapshotHash = hash('sha256', $snapshot);

        $stmt = Database::get()->prepare("
            INSERT INTO balance_closures
                (
                    season_id,
                    income,
                    expenses,
                    profit,
                    total_movements,
                    snapshot_json,
                    approval_status,
                    snapshot_version,
                    snapshot_hash,
                    closed_at
                )
            VALUES
                (?, ?, ?, ?, ?, ?, 'draft', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                income = IF(approval_status = 'approved', income, VALUES(income)),
                expenses = IF(approval_status = 'approved', expenses, VALUES(expenses)),
                profit = IF(approval_status = 'approved', profit, VALUES(profit)),
                total_movements = IF(approval_status = 'approved', total_movements, VALUES(total_movements)),
                snapshot_json = IF(approval_status = 'approved', snapshot_json, VALUES(snapshot_json)),
                snapshot_version = IF(approval_status = 'approved', snapshot_version, VALUES(snapshot_version)),
                snapshot_hash = IF(approval_status = 'approved', snapshot_hash, VALUES(snapshot_hash)),
                closed_at = IF(approval_status = 'approved', closed_at, VALUES(closed_at))
        ");
        $stmt->execute([
            $seasonId,
            $summary['income'],
            $summary['expenses'],
            $summary['profit'],
            $summary['total_movements'],
            $snapshot,
            self::SNAPSHOT_VERSION,
            $snapshotHash
        ]);

        return self::closureForSeason($seasonId);
    }

    public static function approveClosure(int $seasonId, int $userId): array
    {
        $season = Season::find($seasonId);

        if (!$season) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Stagione non trovata'
            ];
        }

        if (($season['status'] ?? null) !== 'chiuso') {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Il bilancio puo essere approvato solo per stagioni chiuse'
            ];
        }

        $closure = self::closeSeason($seasonId);

        if (!$closure) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Chiusura bilancio non disponibile'
            ];
        }

        if (($closure['approval_status'] ?? null) === 'approved') {
            return [
                'success' => true,
                'closure' => $closure,
                'message' => 'Bilancio gia approvato'
            ];
        }

        $stmt = Database::get()->prepare("
            UPDATE balance_closures
            SET
                approval_status = 'approved',
                approved_at = NOW(),
                approved_by = ?
            WHERE season_id = ?
              AND approval_status = 'draft'
        ");
        $stmt->execute([$userId, $seasonId]);

        return [
            'success' => true,
            'closure' => self::closureForSeason($seasonId),
            'message' => 'Bilancio approvato'
        ];
    }

    public static function archiveClosureMovements(int $seasonId): array
    {
        $season = Season::find($seasonId);

        if (!$season) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Stagione non trovata'
            ];
        }

        if (($season['status'] ?? null) !== 'chiuso') {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'I movimenti possono essere storicizzati solo per stagioni chiuse'
            ];
        }

        $db = Database::get();
        $ownsTransaction = !$db->inTransaction();

        try {
            if ($ownsTransaction) {
                $db->beginTransaction();
            }

            $closure = self::closureForSeasonForUpdate($seasonId);

            if (!$closure) {
                if ($ownsTransaction) {
                    $db->rollBack();
                }

                return [
                    'success' => false,
                    'status' => 422,
                    'message' => 'Chiusura bilancio non disponibile'
                ];
            }

            if (($closure['approval_status'] ?? null) !== 'approved') {
                if ($ownsTransaction) {
                    $db->rollBack();
                }

                return [
                    'success' => false,
                    'status' => 422,
                    'message' => 'Il bilancio deve essere approvato prima della storicizzazione'
                ];
            }

            if (!empty($closure['movements_deleted_at'])) {
                if ($ownsTransaction) {
                    $db->commit();
                }

                return [
                    'success' => true,
                    'closure' => $closure,
                    'message' => 'Movimenti gia storicizzati'
                ];
            }

            $countStmt = $db->prepare("SELECT COUNT(*) FROM movements WHERE season_id = ?");
            $countStmt->execute([$seasonId]);
            $movementsCount = (int)$countStmt->fetchColumn();

            $deleteStmt = $db->prepare("DELETE FROM movements WHERE season_id = ?");
            $deleteStmt->execute([$seasonId]);

            $updateStmt = $db->prepare("
                UPDATE balance_closures
                SET
                    movements_deleted_at = NOW(),
                    deleted_movements_count = ?
                WHERE season_id = ?
            ");
            $updateStmt->execute([$movementsCount, $seasonId]);

            if ($ownsTransaction) {
                $db->commit();
            }

            return [
                'success' => true,
                'closure' => self::closureForSeason($seasonId),
                'message' => $movementsCount > 0
                    ? 'Movimenti storicizzati'
                    : 'Nessun movimento da storicizzare'
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    private static function closureSnapshot(array $summary): array
    {
        return [
            'snapshot_version' => self::SNAPSHOT_VERSION,
            'generated_at' => date('c'),
            'source' => 'movements',
            'season' => $summary['season'],
            'summary' => [
                'income' => $summary['income'],
                'expenses' => $summary['expenses'],
                'profit' => $summary['profit'],
                'total_movements' => $summary['total_movements']
            ],
            'categories' => $summary['categories'],
            'competitions' => $summary['competitions'],
            'monthly' => $summary['monthly']
        ];
    }

    private static function closureForSeason(int $seasonId): ?array
    {
        return self::closureForSeasonQuery($seasonId);
    }

    private static function closureForSeasonForUpdate(int $seasonId): ?array
    {
        return self::closureForSeasonQuery($seasonId, true);
    }

    private static function closureForSeasonQuery(int $seasonId, bool $forUpdate = false): ?array
    {
        $stmt = Database::get()->prepare("
            SELECT
                season_id,
                income,
                expenses,
                profit,
                total_movements,
                approval_status,
                approved_at,
                approved_by,
                movements_deleted_at,
                deleted_movements_count,
                snapshot_version,
                snapshot_hash,
                closed_at
            FROM balance_closures
            WHERE season_id = ?
            " . ($forUpdate ? "FOR UPDATE" : "") . "
        ");
        $stmt->execute([$seasonId]);

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return [
            'season_id' => (int)$row['season_id'],
            'income' => (float)$row['income'],
            'expenses' => (float)$row['expenses'],
            'profit' => (float)$row['profit'],
            'total_movements' => (int)$row['total_movements'],
            'approval_status' => $row['approval_status'],
            'approved_at' => $row['approved_at'],
            'approved_by' => $row['approved_by'] !== null ? (int)$row['approved_by'] : null,
            'movements_deleted_at' => $row['movements_deleted_at'],
            'deleted_movements_count' => (int)$row['deleted_movements_count'],
            'snapshot_version' => (int)$row['snapshot_version'],
            'snapshot_hash' => $row['snapshot_hash'],
            'closed_at' => $row['closed_at']
        ];
    }

    private static function summaryForSeasonData(array $season, array $filters = []): array
    {
        $seasonId = (int)$season['id'];
        $filters = self::normalizeFilters($filters, $season['starts_on'] ?? null, $season['ends_on'] ?? null);
        $totals = self::totals($seasonId, $filters);

        return [
            'season' => [
                'id' => $seasonId,
                'name' => $season['name'],
                'starts_on' => $season['starts_on'] ?? null,
                'ends_on' => $season['ends_on'] ?? null
            ],
            'filters' => $filters,
            'filter_options' => [
                'competitions' => self::competitionOptions($seasonId),
                'teams' => self::teamOptions($seasonId),
                'referees' => self::refereeOptions($seasonId)
            ],
            'income' => $totals['income'],
            'expenses' => $totals['expenses'],
            'profit' => $totals['profit'],
            'total_movements' => $totals['total_movements'],
            'categories' => self::categories($seasonId, $filters),
            'competitions' => self::competitions($seasonId, $filters),
            'monthly' => self::monthly($seasonId, $filters)
        ];
    }

    public static function csvExport(array $filters = []): array
    {
        $summary = self::summary($filters);
        $seasonName = $summary['season']['name'] ?? 'senza-stagione';
        $filenameSeason = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $seasonName);

        $handle = fopen('php://temp', 'r+');

        self::writeCsvRow($handle, ['Bilancio', $seasonName]);
        if (!empty($summary['filters']['from']) || !empty($summary['filters']['to'])) {
            self::writeCsvRow($handle, [
                'Periodo',
                ($summary['filters']['from'] ?: 'inizio stagione') . ' - ' . ($summary['filters']['to'] ?: 'fine stagione')
            ]);
        }
        if (!empty($summary['filters']['competition_id'])) {
            self::writeCsvRow($handle, [
                'Competizione',
                self::optionNameFromOptions($summary['filter_options']['competitions'], (int)$summary['filters']['competition_id'])
            ]);
        }
        if (!empty($summary['filters']['team_id'])) {
            self::writeCsvRow($handle, [
                'Squadra',
                self::optionNameFromOptions($summary['filter_options']['teams'], (int)$summary['filters']['team_id'])
            ]);
        }
        if (!empty($summary['filters']['referee_id'])) {
            self::writeCsvRow($handle, [
                'Arbitro',
                self::optionNameFromOptions($summary['filter_options']['referees'], (int)$summary['filters']['referee_id'])
            ]);
        }
        self::writeCsvRow($handle, []);
        self::writeCsvRow($handle, ['Riepilogo']);
        self::writeCsvRow($handle, ['Entrate', self::csvAmount($summary['income'])]);
        self::writeCsvRow($handle, ['Uscite', self::csvAmount($summary['expenses'])]);
        self::writeCsvRow($handle, ['Saldo', self::csvAmount($summary['profit'])]);
        self::writeCsvRow($handle, ['Movimenti', (string)$summary['total_movements']]);

        self::writeCsvRow($handle, []);
        self::writeCsvRow($handle, ['Riepilogo per categoria']);
        self::writeCsvRow($handle, ['Categoria', 'Sottocategoria', 'Entrate', 'Uscite', 'Saldo', 'Movimenti']);
        foreach ($summary['categories'] as $row) {
            self::writeCsvRow($handle, [
                $row['category_name'],
                $row['subcategory_name'],
                self::csvAmount($row['income']),
                self::csvAmount($row['expenses']),
                self::csvAmount($row['profit']),
                (string)$row['total_movements']
            ]);
        }

        self::writeCsvRow($handle, []);
        self::writeCsvRow($handle, ['Riepilogo per competizione']);
        self::writeCsvRow($handle, ['Competizione', 'Entrate', 'Uscite', 'Saldo', 'Movimenti']);
        foreach ($summary['competitions'] as $row) {
            self::writeCsvRow($handle, [
                $row['competition_name'],
                self::csvAmount($row['income']),
                self::csvAmount($row['expenses']),
                self::csvAmount($row['profit']),
                (string)$row['total_movements']
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return [
            'filename' => 'bilancio-' . $filenameSeason . '.csv',
            'content' => "\xEF\xBB\xBF" . $content
        ];
    }

    public static function seasonComparison(): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                s.id AS season_id,
                s.name AS season_name,
                s.starts_on,
                s.ends_on,
                s.status,
                s.is_current,
                bc.closed_at,
                bc.approval_status,
                bc.approved_at,
                bc.movements_deleted_at,
                CASE
                    WHEN bc.approval_status = 'approved' THEN bc.income
                    ELSE COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0)
                END AS income,
                CASE
                    WHEN bc.approval_status = 'approved' THEN bc.expenses
                    ELSE COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0)
                END AS expenses,
                CASE
                    WHEN bc.approval_status = 'approved' THEN bc.total_movements
                    ELSE COUNT(m.id)
                END AS total_movements
            FROM seasons s
            LEFT JOIN balance_closures bc ON bc.season_id = s.id
            LEFT JOIN movements m ON m.season_id = s.id
            LEFT JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            GROUP BY
                s.id, s.name, s.starts_on, s.ends_on, s.status, s.is_current,
                bc.closed_at, bc.approval_status, bc.approved_at,
                bc.movements_deleted_at, bc.income, bc.expenses,
                bc.total_movements
            ORDER BY s.starts_on ASC, s.id ASC
        ");
        $stmt->execute();

        $rows = [];
        $previousProfit = null;

        foreach ($stmt->fetchAll() as $row) {
            $income = (float)$row['income'];
            $expenses = (float)$row['expenses'];
            $profit = $income - $expenses;
            $totalMovements = (int)$row['total_movements'];
            $profitChange = $previousProfit === null || $totalMovements === 0 ? null : $profit - $previousProfit;

            $rows[] = [
                'season_id' => (int)$row['season_id'],
                'season_name' => $row['season_name'],
                'starts_on' => $row['starts_on'],
                'ends_on' => $row['ends_on'],
                'status' => $row['status'],
                'is_current' => (int)$row['is_current'] === 1,
                'closed_at' => $row['closed_at'],
                'approval_status' => $row['approval_status'],
                'approved_at' => $row['approved_at'],
                'movements_deleted_at' => $row['movements_deleted_at'],
                'data_source' => $row['approval_status'] === 'approved' ? 'snapshot' : 'movements',
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $profit,
                'profit_change' => $profitChange,
                'profit_change_percent' => self::percentageChange($profitChange, $previousProfit),
                'total_movements' => $totalMovements
            ];

            if ($totalMovements > 0) {
                $previousProfit = $profit;
            }
        }

        return [
            'rows' => $rows,
            'chart' => [
                'labels' => array_column($rows, 'season_name'),
                'income' => array_column($rows, 'income'),
                'expenses' => array_column($rows, 'expenses'),
                'profit' => array_column($rows, 'profit')
            ]
        ];
    }

    public static function seasonAnalysis(array $filters = []): array
    {
        $seasons = Season::all();
        $seasonOptions = self::seasonOptions($seasons);

        if (count($seasons) < 2) {
            return [
                'season_options' => $seasonOptions,
                'base_season' => null,
                'compare_season' => null,
                'summary' => null,
                'categories' => [],
                'competitions' => [],
                'chart' => [
                    'labels' => [],
                    'income' => [],
                    'expenses' => [],
                    'profit' => []
                ],
                'message' => 'Servono almeno due stagioni per il confronto'
            ];
        }

        $compareSeasonId = self::positiveInt($filters['compare_season_id'] ?? null)
            ?? Season::currentId()
            ?? (int)$seasons[0]['id'];

        $baseSeasonId = self::positiveInt($filters['base_season_id'] ?? null)
            ?? self::previousSeasonId($compareSeasonId, $seasons);

        if ($baseSeasonId === null || $baseSeasonId === $compareSeasonId) {
            $baseSeasonId = self::firstDifferentSeasonId($compareSeasonId, $seasons);
        }

        $baseSeason = $baseSeasonId ? Season::find($baseSeasonId) : null;
        $compareSeason = Season::find($compareSeasonId);

        if (!$baseSeason || !$compareSeason) {
            return [
                'season_options' => $seasonOptions,
                'base_season' => null,
                'compare_season' => null,
                'summary' => null,
                'categories' => [],
                'competitions' => [],
                'chart' => [
                    'labels' => [],
                    'income' => [],
                    'expenses' => [],
                    'profit' => []
                ],
                'message' => 'Stagioni di confronto non valide'
            ];
        }

        $baseSource = self::comparisonSourceForSeason($baseSeasonId);
        $compareSource = self::comparisonSourceForSeason($compareSeasonId);

        return [
            'season_options' => $seasonOptions,
            'base_season' => self::seasonPayload($baseSeason),
            'compare_season' => self::seasonPayload($compareSeason),
            'base_closure' => self::closureForSeason($baseSeasonId),
            'compare_closure' => self::closureForSeason($compareSeasonId),
            'base_source' => $baseSource['source'],
            'compare_source' => $compareSource['source'],
            'summary' => self::comparisonPair(
                $baseSource['summary'],
                $compareSource['summary']
            ),
            'categories' => self::compareAggregates(
                $baseSource['categories'],
                $compareSource['categories'],
                ['category_name', 'subcategory_name']
            ),
            'competitions' => self::compareAggregates(
                $baseSource['competitions'],
                $compareSource['competitions'],
                ['competition_name', 'competition_type', 'football_type']
            ),
            'chart' => [
                'labels' => [$baseSeason['name'], $compareSeason['name']],
                'income' => [$baseSource['summary']['income'], $compareSource['summary']['income']],
                'expenses' => [$baseSource['summary']['expenses'], $compareSource['summary']['expenses']],
                'profit' => [$baseSource['summary']['profit'], $compareSource['summary']['profit']]
            ],
            'message' => null
        ];
    }

    private static function totals(int $seasonId, array $filters): array
    {
        $params = [$seasonId];
        $where = self::movementFilterSql($filters, $params);

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
            $where
        ");
        $stmt->execute($params);

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

    private static function categories(int $seasonId, array $filters): array
    {
        $params = [$seasonId];
        $where = self::movementFilterSql($filters, $params);

        $stmt = Database::get()->prepare("
            SELECT
                COALESCE(c2.name, 'Senza categoria') AS category_name,
                COALESCE(c3.name, 'Senza sottocategoria') AS subcategory_name,
                COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0) AS expenses,
                COUNT(*) AS total_movements
            FROM movements m
            JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            WHERE m.season_id = ?
            $where
            GROUP BY c2.name, c3.name
            ORDER BY c2.name, c3.name
        ");
        $stmt->execute($params);

        return array_map([self::class, 'withProfit'], $stmt->fetchAll());
    }

    private static function competitions(int $seasonId, array $filters): array
    {
        $params = [$seasonId];
        $where = self::movementFilterSql($filters, $params);

        $stmt = Database::get()->prepare("
            SELECT
                COALESCE(c.name, 'Gestione generale') AS competition_name,
                COALESCE(c.type, '') AS competition_type,
                COALESCE(c.football_type, '') AS football_type,
                COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0) AS expenses,
                COUNT(*) AS total_movements
            FROM movements m
            JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE m.season_id = ?
            $where
            GROUP BY c.id, c.name, c.type, c.football_type
            ORDER BY c.name
        ");
        $stmt->execute($params);

        return array_map([self::class, 'withProfit'], $stmt->fetchAll());
    }

    private static function monthly(int $seasonId, array $filters): array
    {
        $months = self::monthsBetween($filters['from'], $filters['to']);
        $params = [$seasonId];
        $where = self::movementFilterSql($filters, $params);

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
            $where
            GROUP BY DATE_FORMAT(m.movement_date, '%Y-%m')
        ");
        $stmt->execute($params);

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

    private static function withProfit(array $row): array
    {
        $income = (float)$row['income'];
        $expenses = (float)$row['expenses'];

        $row['income'] = $income;
        $row['expenses'] = $expenses;
        $row['profit'] = $income - $expenses;
        $row['total_movements'] = (int)$row['total_movements'];

        return $row;
    }

    private static function categoryAggregates(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                COALESCE(c2.name, 'Senza categoria') AS category_name,
                COALESCE(c3.name, 'Senza sottocategoria') AS subcategory_name,
                COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0) AS expenses,
                COUNT(*) AS total_movements
            FROM movements m
            JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            WHERE m.season_id = ?
            GROUP BY c2.name, c3.name
            ORDER BY c2.name, c3.name
        ");
        $stmt->execute([$seasonId]);

        return array_map([self::class, 'withProfit'], $stmt->fetchAll());
    }

    private static function competitionAggregates(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                COALESCE(c.name, 'Gestione generale') AS competition_name,
                COALESCE(c.type, '') AS competition_type,
                COALESCE(c.football_type, '') AS football_type,
                COALESCE(SUM(CASE WHEN c1.name = 'Entrate' THEN m.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN c1.name = 'Uscite' THEN m.amount ELSE 0 END), 0) AS expenses,
                COUNT(*) AS total_movements
            FROM movements m
            JOIN categories c4 ON c4.id = m.category_id
            LEFT JOIN categories c3 ON c3.id = c4.parent_id
            LEFT JOIN categories c2 ON c2.id = c3.parent_id
            LEFT JOIN categories c1 ON c1.id = c2.parent_id
            LEFT JOIN competitions c ON c.id = m.competition_id
            WHERE m.season_id = ?
            GROUP BY c.name, c.type, c.football_type
            ORDER BY c.name
        ");
        $stmt->execute([$seasonId]);

        return array_map([self::class, 'withProfit'], $stmt->fetchAll());
    }

    private static function comparisonSourceForSeason(int $seasonId): array
    {
        $snapshot = self::approvedSnapshotForSeason($seasonId);

        if ($snapshot) {
            return [
                'source' => 'snapshot',
                'summary' => self::snapshotSummary($snapshot),
                'categories' => self::snapshotCategories($snapshot),
                'competitions' => self::snapshotCompetitions($snapshot)
            ];
        }

        return [
            'source' => 'movements',
            'summary' => self::summaryComparisonSource(self::summaryForSeason($seasonId)),
            'categories' => self::categoryAggregates($seasonId),
            'competitions' => self::competitionAggregates($seasonId)
        ];
    }

    private static function approvedSnapshotForSeason(int $seasonId): ?array
    {
        $stmt = Database::get()->prepare("
            SELECT snapshot_json
            FROM balance_closures
            WHERE season_id = ?
              AND approval_status = 'approved'
              AND snapshot_json IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([$seasonId]);

        $snapshotJson = $stmt->fetchColumn();
        if (!$snapshotJson) {
            return null;
        }

        $snapshot = json_decode((string)$snapshotJson, true);

        return is_array($snapshot) ? $snapshot : null;
    }

    private static function snapshotSummary(array $snapshot): array
    {
        $summary = is_array($snapshot['summary'] ?? null)
            ? $snapshot['summary']
            : $snapshot;

        return self::comparisonValues($summary);
    }

    private static function snapshotCategories(array $snapshot): array
    {
        $rows = is_array($snapshot['categories'] ?? null) ? $snapshot['categories'] : [];

        return self::normalizeSnapshotRows($rows, [
            'category_name' => 'Senza categoria',
            'subcategory_name' => 'Senza sottocategoria'
        ]);
    }

    private static function snapshotCompetitions(array $snapshot): array
    {
        $rows = is_array($snapshot['competitions'] ?? null) ? $snapshot['competitions'] : [];

        return self::normalizeSnapshotRows($rows, [
            'competition_name' => 'Gestione generale',
            'competition_type' => '',
            'football_type' => ''
        ]);
    }

    private static function normalizeSnapshotRows(array $rows, array $defaults): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized[] = self::withProfit(array_merge([
                'income' => 0,
                'expenses' => 0,
                'total_movements' => 0
            ], $defaults, $row));
        }

        return $normalized;
    }

    private static function compareAggregates(array $baseRows, array $compareRows, array $identityFields): array
    {
        $indexed = [];

        foreach ($baseRows as $row) {
            $key = self::comparisonKey($row, $identityFields);
            $indexed[$key]['identity'] = self::identityPayload($row, $identityFields);
            $indexed[$key]['base'] = $row;
        }

        foreach ($compareRows as $row) {
            $key = self::comparisonKey($row, $identityFields);
            $indexed[$key]['identity'] = self::identityPayload($row, $identityFields);
            $indexed[$key]['compare'] = $row;
        }

        ksort($indexed);

        $rows = [];
        foreach ($indexed as $item) {
            $rows[] = array_merge(
                $item['identity'],
                self::comparisonPair($item['base'] ?? [], $item['compare'] ?? [])
            );
        }

        return $rows;
    }

    private static function comparisonPair(array $baseRow, array $compareRow): array
    {
        $base = self::comparisonValues($baseRow);
        $compare = self::comparisonValues($compareRow);
        $profitChange = $compare['profit'] - $base['profit'];

        return [
            'base' => $base,
            'compare' => $compare,
            'income_change' => $compare['income'] - $base['income'],
            'expenses_change' => $compare['expenses'] - $base['expenses'],
            'profit_change' => $profitChange,
            'profit_change_percent' => self::percentageChange($profitChange, $base['profit']),
            'total_movements_change' => $compare['total_movements'] - $base['total_movements']
        ];
    }

    private static function comparisonValues(array $row): array
    {
        return [
            'income' => (float)($row['income'] ?? 0),
            'expenses' => (float)($row['expenses'] ?? 0),
            'profit' => (float)($row['profit'] ?? 0),
            'total_movements' => (int)($row['total_movements'] ?? 0)
        ];
    }

    private static function summaryComparisonSource(array $summary): array
    {
        return [
            'income' => $summary['income'] ?? 0,
            'expenses' => $summary['expenses'] ?? 0,
            'profit' => $summary['profit'] ?? 0,
            'total_movements' => $summary['total_movements'] ?? 0
        ];
    }

    private static function comparisonKey(array $row, array $identityFields): string
    {
        $values = [];

        foreach ($identityFields as $field) {
            $values[] = (string)($row[$field] ?? '');
        }

        return implode("\0", $values);
    }

    private static function identityPayload(array $row, array $identityFields): array
    {
        $identity = [];

        foreach ($identityFields as $field) {
            $identity[$field] = $row[$field] ?? null;
        }

        return $identity;
    }

    private static function seasonOptions(array $seasons): array
    {
        return array_map([self::class, 'seasonPayload'], $seasons);
    }

    private static function seasonPayload(array $season): array
    {
        return [
            'id' => (int)$season['id'],
            'name' => $season['name'],
            'starts_on' => $season['starts_on'] ?? null,
            'ends_on' => $season['ends_on'] ?? null,
            'status' => $season['status'] ?? null,
            'is_current' => (int)($season['is_current'] ?? 0) === 1
        ];
    }

    private static function previousSeasonId(int $compareSeasonId, array $seasons): ?int
    {
        $ordered = array_reverse($seasons);
        $previousId = null;

        foreach ($ordered as $season) {
            $seasonId = (int)$season['id'];

            if ($seasonId === $compareSeasonId) {
                return $previousId;
            }

            $previousId = $seasonId;
        }

        return null;
    }

    private static function firstDifferentSeasonId(int $seasonId, array $seasons): ?int
    {
        foreach ($seasons as $season) {
            if ((int)$season['id'] !== $seasonId) {
                return (int)$season['id'];
            }
        }

        return null;
    }

    private static function writeCsvRow($handle, array $row): void
    {
        fputcsv($handle, $row, ';');
    }

    private static function csvAmount($value): string
    {
        return number_format((float)$value, 2, ',', '');
    }

    private static function percentageChange(?float $change, ?float $base): ?float
    {
        if ($change === null || $base === null || abs($base) < 0.01) {
            return null;
        }

        return ($change / abs($base)) * 100;
    }

    private static function movementFilterSql(array $filters, array &$params): string
    {
        $sql = '';

        if (!empty($filters['from'])) {
            $sql .= ' AND m.movement_date >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND m.movement_date <= ?';
            $params[] = $filters['to'];
        }

        if (!empty($filters['competition_id'])) {
            $sql .= ' AND m.competition_id = ?';
            $params[] = (int)$filters['competition_id'];
        }

        if (!empty($filters['team_id'])) {
            $sql .= ' AND m.team_id = ?';
            $params[] = (int)$filters['team_id'];
        }

        if (!empty($filters['referee_id'])) {
            $sql .= ' AND m.referee_id = ?';
            $params[] = (int)$filters['referee_id'];
        }

        return $sql;
    }

    private static function normalizeFilters(array $filters, ?string $seasonStart, ?string $seasonEnd): array
    {
        $from = self::validDate($filters['from'] ?? null) ? $filters['from'] : $seasonStart;
        $to = self::validDate($filters['to'] ?? null) ? $filters['to'] : $seasonEnd;

        if ($seasonStart && $from && $from < $seasonStart) {
            $from = $seasonStart;
        }

        if ($seasonEnd && $to && $to > $seasonEnd) {
            $to = $seasonEnd;
        }

        if ($from && $to && $from > $to) {
            $from = $seasonStart;
            $to = $seasonEnd;
        }

        return [
            'from' => $from,
            'to' => $to,
            'competition_id' => self::positiveInt($filters['competition_id'] ?? null),
            'team_id' => self::positiveInt($filters['team_id'] ?? null),
            'referee_id' => self::positiveInt($filters['referee_id'] ?? null)
        ];
    }

    private static function competitionOptions(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT DISTINCT c.id, c.name
            FROM movements m
            JOIN competitions c ON c.id = m.competition_id
            WHERE m.season_id = ?
            ORDER BY c.name
        ");
        $stmt->execute([$seasonId]);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'name' => $row['name']
            ];
        }, $stmt->fetchAll());
    }

    private static function teamOptions(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT DISTINCT t.id, t.name
            FROM movements m
            JOIN teams t ON t.id = m.team_id
            WHERE m.season_id = ?
            ORDER BY t.name
        ");
        $stmt->execute([$seasonId]);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'name' => $row['name']
            ];
        }, $stmt->fetchAll());
    }

    private static function refereeOptions(int $seasonId): array
    {
        $stmt = Database::get()->prepare("
            SELECT DISTINCT r.id, r.name
            FROM movements m
            JOIN referees r ON r.id = m.referee_id
            WHERE m.season_id = ?
            ORDER BY r.name
        ");
        $stmt->execute([$seasonId]);

        return array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'name' => $row['name']
            ];
        }, $stmt->fetchAll());
    }

    private static function optionNameFromOptions(array $options, int $id): string
    {
        foreach ($options as $option) {
            if ((int)$option['id'] === $id) {
                return $option['name'];
            }
        }

        return 'ID ' . $id;
    }

    private static function positiveInt($value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value) || (int)$value <= 0) {
            return null;
        }

        return (int)$value;
    }

    private static function validDate($value): bool
    {
        if (!$value || !is_string($value)) {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }

    private static function monthsBetween(?string $startsOn, ?string $endsOn): array
    {
        if (!$startsOn || !$endsOn) {
            return [];
        }

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

    private static function emptySummary(): array
    {
        return [
            'season' => null,
            'filters' => [
                'from' => null,
                'to' => null,
                'competition_id' => null,
                'team_id' => null,
                'referee_id' => null
            ],
            'filter_options' => [
                'competitions' => [],
                'teams' => [],
                'referees' => []
            ],
            'income' => 0,
            'expenses' => 0,
            'profit' => 0,
            'total_movements' => 0,
            'categories' => [],
            'competitions' => [],
            'monthly' => [
                'labels' => [],
                'income' => [],
                'expenses' => [],
                'profit' => []
            ]
        ];
    }
}
