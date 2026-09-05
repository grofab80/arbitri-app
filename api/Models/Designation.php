<?php
namespace Api\Models;

use Api\Core\Database;
use Api\Models\RefereeAvailability;

class Designation {

    public static function matches(array $filters = []): array
    {
        $seasonId = Season::currentId();
        $params = [$seasonId];
        $where = ["m.season_id = ?"];

        if (!empty($filters['football_type'])) {
            $where[] = "c.football_type = ?";
            $params[] = (string)$filters['football_type'];
        }

        if (!empty($filters['competition_id'])) {
            $where[] = "m.competition_id = ?";
            $params[] = (int)$filters['competition_id'];
        }

        if (!empty($filters['match_day'])) {
            $where[] = "m.match_day = ?";
            $params[] = (int)$filters['match_day'];
        }

        $stmt = Database::get()->prepare("
            SELECT
                m.id AS match_id,
                m.match_day,
                m.difficulty_rating,
                m.match_date,
                m.match_time,
                m.status AS match_status,
                c.id AS competition_id,
                c.name AS competition_name,
                c.football_type,
                ht.id AS home_team_id,
                ht.name AS home_team_name,
                at.id AS away_team_id,
                at.name AS away_team_name,
                f.name AS field_name,
                d.id AS designation_id,
                d.referee_id AS designation_referee_id,
                d.status AS designation_status,
                d.assignment_type,
                d.score,
                d.score_details_json,
                d.notes AS designation_notes,
                r.name AS referee_name,
                r.rating AS referee_rating
            FROM matches m
            JOIN competitions c ON c.id = m.competition_id
            JOIN teams ht ON ht.id = m.home_team_id
            JOIN teams at ON at.id = m.away_team_id
            LEFT JOIN fields f ON f.id = m.field_id
            LEFT JOIN designations d ON d.match_id = m.id
            LEFT JOIN referees r ON r.id = d.referee_id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY c.name, m.match_day, m.match_date, m.match_time, ht.name
        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function refereeOptions(string $footballType): array
    {
        $column = self::footballTypeColumn($footballType);

        if ($column === null) {
            return [];
        }

        $stmt = Database::get()->prepare("
            SELECT
                id,
                name,
                rating,
                can_referee_11,
                can_referee_7,
                can_referee_5
            FROM referees
            WHERE $column = 1
            ORDER BY rating DESC, name
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function attachAvailability(array $matches, array $referees): array
    {
        foreach ($matches as &$match) {
            $match['availability_checked'] = !empty($match['match_date']) && !empty($match['match_time']);
            $match['available_referee_ids'] = [];
            $match['unavailable_referee_ids'] = [];

            if (!$match['availability_checked']) {
                continue;
            }

            foreach ($referees as $referee) {
                $refereeId = (int)$referee['id'];
                $isAvailable = RefereeAvailability::isAvailableForSlot(
                    $refereeId,
                    (string)$match['match_date'],
                    (string)$match['match_time']
                );

                if ($isAvailable) {
                    $match['available_referee_ids'][] = $refereeId;
                } else {
                    $match['unavailable_referee_ids'][] = $refereeId;
                }
            }
        }
        unset($match);

        return $matches;
    }


    public static function summary(array $filters = []): array
    {
        $matches = self::matches($filters);
        $summary = [
            'total_matches' => count($matches),
            'to_designate' => 0,
            'proposed' => 0,
            'confirmed' => 0,
            'manual' => 0,
            'top_referees' => []
        ];

        $refereeUsage = [];

        foreach ($matches as $match) {
            $status = $match['designation_status'] ?? null;

            if (!$status || empty($match['designation_referee_id'])) {
                $summary['to_designate']++;
            } elseif ($status === 'proposta') {
                $summary['proposed']++;
            } elseif ($status === 'confermata') {
                $summary['confirmed']++;
            } elseif ($status === 'modificata') {
                $summary['manual']++;
            }

            if (!empty($match['designation_referee_id'])) {
                $refereeId = (int)$match['designation_referee_id'];

                if (!isset($refereeUsage[$refereeId])) {
                    $refereeUsage[$refereeId] = [
                        'referee_id' => $refereeId,
                        'referee_name' => $match['referee_name'] ?: 'Arbitro',
                        'total' => 0,
                        'confirmed' => 0,
                        'proposed' => 0,
                        'manual' => 0
                    ];
                }

                $refereeUsage[$refereeId]['total']++;

                if ($status === 'confermata') {
                    $refereeUsage[$refereeId]['confirmed']++;
                } elseif ($status === 'proposta') {
                    $refereeUsage[$refereeId]['proposed']++;
                } elseif ($status === 'modificata') {
                    $refereeUsage[$refereeId]['manual']++;
                }
            }
        }

        usort($refereeUsage, function ($a, $b) {
            if ($a['total'] !== $b['total']) {
                return $b['total'] <=> $a['total'];
            }

            return strcmp($a['referee_name'], $b['referee_name']);
        });

        $summary['top_referees'] = array_slice($refereeUsage, 0, 5);

        return $summary;
    }

    public static function assignManual(int $matchId, ?int $refereeId, int $userId): array
    {
        $match = self::matchWithCompetition($matchId);

        if (!$match) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Partita non trovata'
            ];
        }

        if ($refereeId !== null && !self::refereeCanDesignate($refereeId, $match['football_type'])) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Arbitro non abilitato per questa disciplina'
            ];
        }

        $db = Database::get();
        $ownsTransaction = !$db->inTransaction();

        if ($ownsTransaction) {
            $db->beginTransaction();
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO designations
                    (match_id, referee_id, status, assignment_type, created_by, updated_by)
                VALUES
                    (?, ?, 'modificata', 'manual', ?, ?)
                ON DUPLICATE KEY UPDATE
                    referee_id = VALUES(referee_id),
                    status = 'modificata',
                    assignment_type = 'manual',
                    updated_by = VALUES(updated_by)
            ");
            $stmt->execute([$matchId, $refereeId, $userId, $userId]);

            $matchStmt = $db->prepare("UPDATE matches SET referee_id = ? WHERE id = ?");
            $matchStmt->execute([$refereeId, $matchId]);

            if ($ownsTransaction) {
                $db->commit();
            }

            return [
                'success' => true,
                'designation' => self::findByMatch($matchId)
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public static function generateProposals(array $filters, int $userId): array
    {
        $matches = self::proposalMatches($filters);
        $assignmentLoad = self::refereeAssignmentLoad();
        $generated = 0;
        $skipped = [];

        $db = Database::get();
        $ownsTransaction = !$db->inTransaction();

        if ($ownsTransaction) {
            $db->beginTransaction();
        }

        try {
            foreach ($matches as $match) {
                if (in_array($match['designation_status'], ['modificata', 'confermata'], true)) {
                    $skipped[] = [
                        'match_id' => (int)$match['match_id'],
                        'reason' => 'Designazione manuale o confermata'
                    ];
                    continue;
                }

                $proposal = self::bestProposalForMatch($match, $assignmentLoad);

                if ($proposal === null) {
                    $skipped[] = [
                        'match_id' => (int)$match['match_id'],
                        'reason' => 'Nessun arbitro disponibile'
                    ];
                    continue;
                }

                $stmt = $db->prepare("
                    INSERT INTO designations
                        (
                            match_id,
                            referee_id,
                            status,
                            assignment_type,
                            score,
                            score_details_json,
                            notes,
                            created_by,
                            updated_by
                        )
                    VALUES
                        (?, ?, 'proposta', 'auto', ?, ?, 'Proposta automatica', ?, ?)
                    ON DUPLICATE KEY UPDATE
                        referee_id = VALUES(referee_id),
                        status = 'proposta',
                        assignment_type = 'auto',
                        score = VALUES(score),
                        score_details_json = VALUES(score_details_json),
                        notes = VALUES(notes),
                        updated_by = VALUES(updated_by)
                ");
                $stmt->execute([
                    (int)$match['match_id'],
                    (int)$proposal['referee']['id'],
                    $proposal['score'],
                    json_encode($proposal['details'], JSON_UNESCAPED_UNICODE),
                    $userId,
                    $userId
                ]);

                $matchStmt = $db->prepare("UPDATE matches SET referee_id = ? WHERE id = ?");
                $matchStmt->execute([(int)$proposal['referee']['id'], (int)$match['match_id']]);

                $refereeId = (int)$proposal['referee']['id'];
                $assignmentLoad[$refereeId] = ($assignmentLoad[$refereeId] ?? 0) + 1;
                $generated++;
            }

            if ($ownsTransaction) {
                $db->commit();
            }

            return [
                'generated' => $generated,
                'skipped' => $skipped,
                'total' => count($matches)
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public static function regenerateUnconfirmed(array $filters, int $userId): array
    {
        return self::generateProposals($filters, $userId);
    }

    public static function confirm(int $matchId, int $userId): array
    {
        $designation = self::findByMatch($matchId);

        if (!$designation) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Designazione non trovata'
            ];
        }

        if (empty($designation['referee_id'])) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Impossibile confermare una designazione senza arbitro'
            ];
        }

        $stmt = Database::get()->prepare("
            UPDATE designations
            SET status = 'confermata',
                updated_by = ?
            WHERE match_id = ?
        ");
        $stmt->execute([$userId, $matchId]);

        return [
            'success' => true,
            'designation' => self::findByMatch($matchId)
        ];
    }

    public static function confirmFilteredProposals(array $filters, int $userId): array
    {
        $matches = self::matches($filters);
        $confirmed = 0;
        $skipped = 0;

        $stmt = Database::get()->prepare("
            UPDATE designations
            SET status = 'confermata',
                updated_by = ?
            WHERE match_id = ?
              AND status = 'proposta'
              AND referee_id IS NOT NULL
        ");

        foreach ($matches as $match) {
            if ($match['designation_status'] !== 'proposta' || empty($match['designation_referee_id'])) {
                $skipped++;
                continue;
            }

            $stmt->execute([$userId, (int)$match['match_id']]);

            if ($stmt->rowCount() > 0) {
                $confirmed++;
            } else {
                $skipped++;
            }
        }

        return [
            'confirmed' => $confirmed,
            'skipped' => $skipped,
            'total' => count($matches)
        ];
    }

    public static function clearAutomaticProposals(array $filters): array
    {
        $matches = self::matches($filters);
        $cleared = 0;
        $skipped = 0;
        $db = Database::get();
        $ownsTransaction = !$db->inTransaction();

        if ($ownsTransaction) {
            $db->beginTransaction();
        }

        try {
            foreach ($matches as $match) {
                if (
                    $match['designation_status'] !== 'proposta'
                    || $match['assignment_type'] !== 'auto'
                    || empty($match['designation_id'])
                ) {
                    $skipped++;
                    continue;
                }

                $matchStmt = $db->prepare("
                    UPDATE matches
                    SET referee_id = NULL
                    WHERE id = ?
                      AND referee_id = ?
                ");
                $matchStmt->execute([
                    (int)$match['match_id'],
                    (int)$match['designation_referee_id']
                ]);

                $deleteStmt = $db->prepare("
                    DELETE FROM designations
                    WHERE id = ?
                      AND status = 'proposta'
                      AND assignment_type = 'auto'
                ");
                $deleteStmt->execute([(int)$match['designation_id']]);

                if ($deleteStmt->rowCount() > 0) {
                    $cleared++;
                } else {
                    $skipped++;
                }
            }

            if ($ownsTransaction) {
                $db->commit();
            }

            return [
                'cleared' => $cleared,
                'skipped' => $skipped,
                'total' => count($matches)
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    public static function blacklist(): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                b.id,
                b.referee_id,
                r.name AS referee_name,
                b.team_id,
                t.name AS team_name,
                b.reason,
                b.active,
                b.created_at,
                b.updated_at
            FROM referee_team_blacklist b
            JOIN referees r ON r.id = b.referee_id
            JOIN teams t ON t.id = b.team_id
            WHERE b.active = 1
            ORDER BY r.name, t.name
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function addBlacklist(int $refereeId, int $teamId, ?string $reason = null): int
    {
        $stmt = Database::get()->prepare("
            INSERT INTO referee_team_blacklist
                (referee_id, team_id, reason, active)
            VALUES
                (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                active = 1,
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $refereeId,
            $teamId,
            $reason !== null ? trim($reason) : null
        ]);

        $find = Database::get()->prepare("
            SELECT id
            FROM referee_team_blacklist
            WHERE referee_id = ?
              AND team_id = ?
            LIMIT 1
        ");
        $find->execute([$refereeId, $teamId]);

        return (int)$find->fetchColumn();
    }

    public static function removeBlacklist(int $id): bool
    {
        $stmt = Database::get()->prepare("
            UPDATE referee_team_blacklist
            SET active = 0,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    public static function findByMatch(int $matchId): ?array
    {
        $stmt = Database::get()->prepare("SELECT * FROM designations WHERE match_id = ?");
        $stmt->execute([$matchId]);

        return $stmt->fetch() ?: null;
    }

    private static function matchWithCompetition(int $matchId): ?array
    {
        $stmt = Database::get()->prepare("
            SELECT m.*, c.football_type
            FROM matches m
            JOIN competitions c ON c.id = m.competition_id
            WHERE m.id = ?
        ");
        $stmt->execute([$matchId]);

        return $stmt->fetch() ?: null;
    }

    private static function proposalMatches(array $filters): array
    {
        $seasonId = Season::currentId();
        $params = [$seasonId];
        $where = ["m.season_id = ?"];

        if (!empty($filters['football_type'])) {
            $where[] = "c.football_type = ?";
            $params[] = (string)$filters['football_type'];
        }

        if (!empty($filters['competition_id'])) {
            $where[] = "m.competition_id = ?";
            $params[] = (int)$filters['competition_id'];
        }

        if (!empty($filters['match_day'])) {
            $where[] = "m.match_day = ?";
            $params[] = (int)$filters['match_day'];
        }

        $stmt = Database::get()->prepare("
            SELECT
                m.id AS match_id,
                m.match_day,
                m.difficulty_rating,
                m.match_date,
                m.match_time,
                m.home_team_id,
                m.away_team_id,
                c.football_type,
                f.latitude AS field_latitude,
                f.longitude AS field_longitude,
                d.status AS designation_status,
                d.assignment_type
            FROM matches m
            JOIN competitions c ON c.id = m.competition_id
            LEFT JOIN fields f ON f.id = m.field_id
            LEFT JOIN designations d ON d.match_id = m.id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY m.match_date, m.match_time, m.id
        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private static function bestProposalForMatch(array $match, array $assignmentLoad): ?array
    {
        $candidates = self::proposalCandidates((string)$match['football_type']);
        $best = null;

        foreach ($candidates as $referee) {
            $evaluation = self::evaluateCandidate($match, $referee, $assignmentLoad);

            if (!$evaluation['eligible']) {
                continue;
            }

            if ($best === null || $evaluation['score'] > $best['score']) {
                $best = [
                    'referee' => $referee,
                    'score' => $evaluation['score'],
                    'details' => $evaluation['details']
                ];
            }
        }

        return $best;
    }

    private static function proposalCandidates(string $footballType): array
    {
        $column = self::footballTypeColumn($footballType);

        if ($column === null) {
            return [];
        }

        $stmt = Database::get()->prepare("
            SELECT
                id,
                name,
                rating,
                latitude,
                longitude
            FROM referees
            WHERE $column = 1
            ORDER BY rating DESC, name
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private static function evaluateCandidate(array $match, array $referee, array $assignmentLoad): array
    {
        $scoreConfig = self::scoreConfig();
        $refereeId = (int)$referee['id'];
        $details = [
            'rating_match' => (int)$match['difficulty_rating'],
            'rating_referee' => (int)$referee['rating'],
            'distance_km' => null,
            'assignment_load' => (int)($assignmentLoad[$refereeId] ?? 0),
            'consecutive_team_conflict' => false,
            'consecutive_home_team_conflict' => false,
            'availability_checked' => !empty($match['match_date']) && !empty($match['match_time']),
            'availability_required' => true,
            'minimum_minutes_between_matches' => self::minimumMinutesBetweenMatches(),
            'score_weights' => $scoreConfig,
            'penalties' => []
        ];

        if (self::isBlacklisted($refereeId, (int)$match['home_team_id'], (int)$match['away_team_id'])) {
            return [
                'eligible' => false,
                'score' => 0,
                'details' => $details + ['excluded_reason' => 'blacklist']
            ];
        }

        if (!empty($match['match_date']) && !empty($match['match_time'])) {
            $isAvailable = RefereeAvailability::isAvailableForSlot(
                $refereeId,
                (string)$match['match_date'],
                (string)$match['match_time']
            );

            if (!$isAvailable) {
                return [
                    'eligible' => false,
                    'score' => 0,
                    'details' => $details + ['excluded_reason' => 'availability']
                ];
            }
        }

        $timeConflict = self::sameDayTimeConflict($refereeId, $match);
        if ($timeConflict !== null) {
            return [
                'eligible' => false,
                'score' => 0,
                'details' => $details + [
                    'excluded_reason' => 'time_window',
                    'time_conflict' => $timeConflict
                ]
            ];
        }

        $score = $scoreConfig['base'];
        $ratingGap = abs((int)$referee['rating'] - (int)$match['difficulty_rating']);
        $ratingPenalty = $ratingGap * $scoreConfig['rating_gap_penalty'];
        $score -= $ratingPenalty;
        $details['penalties']['rating_gap'] = $ratingPenalty;

        $loadPenalty = min(
            $scoreConfig['assignment_load_max_penalty'],
            $details['assignment_load'] * $scoreConfig['assignment_load_penalty']
        );
        $score -= $loadPenalty;
        $details['penalties']['assignment_load'] = $loadPenalty;

        $consecutiveConflict = self::consecutiveTeamConflict($refereeId, $match);
        if ($consecutiveConflict['same_team']) {
            $score -= $scoreConfig['consecutive_team_penalty'];
            $details['consecutive_team_conflict'] = true;
            $details['penalties']['consecutive_team'] = $scoreConfig['consecutive_team_penalty'];
        }

        if ($consecutiveConflict['same_home_team']) {
            $score -= $scoreConfig['consecutive_home_team_penalty'];
            $details['consecutive_home_team_conflict'] = true;
            $details['penalties']['consecutive_home_team'] = $scoreConfig['consecutive_home_team_penalty'];
        }

        $distance = self::distanceKm(
            $referee['latitude'] ?? null,
            $referee['longitude'] ?? null,
            $match['field_latitude'] ?? null,
            $match['field_longitude'] ?? null
        );

        if ($distance !== null) {
            $distancePenalty = min(
                $scoreConfig['distance_max_penalty'],
                $distance / max(1, $scoreConfig['distance_km_divisor'])
            );
            $score -= $distancePenalty;
            $details['distance_km'] = round($distance, 2);
            $details['penalties']['distance'] = round($distancePenalty, 2);
        }

        return [
            'eligible' => true,
            'score' => round(max(0, $score), 2),
            'details' => $details
        ];
    }

    private static function isBlacklisted(int $refereeId, int $homeTeamId, int $awayTeamId): bool
    {
        $stmt = Database::get()->prepare("
            SELECT COUNT(*)
            FROM referee_team_blacklist
            WHERE referee_id = ?
              AND team_id IN (?, ?)
              AND active = 1
        ");
        $stmt->execute([$refereeId, $homeTeamId, $awayTeamId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private static function refereeAssignmentLoad(): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                d.referee_id,
                COUNT(*) AS assignments
            FROM designations d
            JOIN matches m ON m.id = d.match_id
            WHERE m.season_id = ?
              AND d.referee_id IS NOT NULL
            GROUP BY d.referee_id
        ");
        $stmt->execute([Season::currentId()]);

        $load = [];
        foreach ($stmt->fetchAll() as $row) {
            $load[(int)$row['referee_id']] = (int)$row['assignments'];
        }

        return $load;
    }

    private static function sameDayTimeConflict(int $refereeId, array $match): ?array
    {
        if (empty($match['match_time'])) {
            return self::sameDayUntimedConflict($refereeId, $match);
        }

        $stmt = Database::get()->prepare("
            SELECT
                m.id,
                m.match_time,
                ABS(TIME_TO_SEC(TIMEDIFF(m.match_time, ?)) / 60) AS minutes_diff
            FROM designations d
            JOIN matches m ON m.id = d.match_id
            WHERE d.referee_id = ?
              AND m.id <> ?
              AND m.match_date = ?
              AND m.match_time IS NOT NULL
              AND ABS(TIME_TO_SEC(TIMEDIFF(m.match_time, ?)) / 60) < ?
            ORDER BY minutes_diff ASC
            LIMIT 1
        ");
        $stmt->execute([
            $match['match_time'],
            $refereeId,
            (int)$match['match_id'],
            $match['match_date'],
            $match['match_time'],
            self::minimumMinutesBetweenMatches()
        ]);

        $conflict = $stmt->fetch();

        if (!$conflict) {
            return null;
        }

        return [
            'match_id' => (int)$conflict['id'],
            'match_time' => $conflict['match_time'],
            'minutes_diff' => (int)round((float)$conflict['minutes_diff'])
        ];
    }

    private static function sameDayUntimedConflict(int $refereeId, array $match): ?array
    {
        $stmt = Database::get()->prepare("
            SELECT
                m.id,
                m.match_time
            FROM designations d
            JOIN matches m ON m.id = d.match_id
            WHERE d.referee_id = ?
              AND m.id <> ?
              AND m.match_date = ?
            ORDER BY COALESCE(m.match_time, '00:00:00'), m.id
            LIMIT 1
        ");
        $stmt->execute([
            $refereeId,
            (int)$match['match_id'],
            $match['match_date']
        ]);

        $conflict = $stmt->fetch();

        if (!$conflict) {
            return null;
        }

        return [
            'match_id' => (int)$conflict['id'],
            'match_time' => $conflict['match_time'],
            'minutes_diff' => null
        ];
    }

    private static function consecutiveTeamConflict(int $refereeId, array $match): array
    {
        $stmt = Database::get()->prepare("
            SELECT
                m.home_team_id,
                m.away_team_id
            FROM designations d
            JOIN matches m ON m.id = d.match_id
            WHERE d.referee_id = ?
              AND (
                    m.match_date < ?
                    OR (m.match_date = ? AND COALESCE(m.match_time, '00:00:00') < COALESCE(?, '00:00:00'))
                    OR (m.match_date = ? AND COALESCE(m.match_time, '00:00:00') = COALESCE(?, '00:00:00') AND m.id < ?)
              )
            ORDER BY m.match_date DESC, COALESCE(m.match_time, '00:00:00') DESC, m.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            $refereeId,
            $match['match_date'],
            $match['match_date'],
            $match['match_time'],
            $match['match_date'],
            $match['match_time'],
            (int)$match['match_id']
        ]);

        $previous = $stmt->fetch();

        if (!$previous) {
            return [
                'same_team' => false,
                'same_home_team' => false
            ];
        }

        $currentTeams = [(int)$match['home_team_id'], (int)$match['away_team_id']];
        $previousTeams = [(int)$previous['home_team_id'], (int)$previous['away_team_id']];

        return [
            'same_team' => !empty(array_intersect($currentTeams, $previousTeams)),
            'same_home_team' => (int)$previous['home_team_id'] === (int)$match['home_team_id']
        ];
    }

    private static function distanceKm($lat1, $lon1, $lat2, $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $lat1 = (float)$lat1;
        $lon1 = (float)$lon1;
        $lat2 = (float)$lat2;
        $lon2 = (float)$lon2;

        if ($lat1 == 0.0 || $lon1 == 0.0 || $lat2 == 0.0 || $lon2 == 0.0) {
            return null;
        }

        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private static function scoreConfig(): array
    {
        $defaults = [
            'base' => 100.0,
            'rating_gap_penalty' => 18.0,
            'assignment_load_penalty' => 6.0,
            'assignment_load_max_penalty' => 30.0,
            'consecutive_team_penalty' => 35.0,
            'consecutive_home_team_penalty' => 15.0,
            'distance_km_divisor' => 5.0,
            'distance_max_penalty' => 25.0
        ];

        $config = self::designationConfig();
        $score = is_array($config['score'] ?? null) ? $config['score'] : [];

        foreach ($defaults as $key => $value) {
            $score[$key] = isset($score[$key]) && is_numeric($score[$key])
                ? (float)$score[$key]
                : $value;
        }

        return $score;
    }

    private static function minimumMinutesBetweenMatches(): int
    {
        $config = self::designationConfig();
        $value = $config['constraints']['minimum_minutes_between_matches'] ?? 120;

        return max(0, (int)$value);
    }

    private static function designationConfig(): array
    {
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $path = __DIR__ . '/../../config/designations.php';
        $loaded = file_exists($path) ? require $path : [];

        $config = is_array($loaded) ? $loaded : [];

        return $config;
    }

    private static function refereeCanDesignate(int $refereeId, string $footballType): bool
    {
        $column = self::footballTypeColumn($footballType);

        if ($column === null) {
            return false;
        }

        $stmt = Database::get()->prepare("SELECT COUNT(*) FROM referees WHERE id = ? AND $column = 1");
        $stmt->execute([$refereeId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private static function footballTypeColumn(string $footballType): ?string
    {
        $map = [
            '11' => 'can_referee_11',
            '7' => 'can_referee_7',
            '5' => 'can_referee_5'
        ];

        return $map[$footballType] ?? null;
    }
}
