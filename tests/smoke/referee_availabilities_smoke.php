<?php

require __DIR__ . '/../../api/v1/bootstrap.php';

use Api\Core\Database;
use Api\Models\Designation;
use Api\Models\RefereeAvailability;
use Api\Models\Season;
use Api\Validators\RefereeAvailabilityValidator;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function pass(string $message): void
{
    echo "[PASS] {$message}" . PHP_EOL;
}

function firstValue(PDO $db, string $sql, array $params = [])
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

$db = Database::get();
$db->beginTransaction();

try {
    $userId = (int)firstValue($db, "SELECT id FROM users ORDER BY id LIMIT 1");
    assertTrue($userId > 0, 'Nessun utente disponibile per created_by/updated_by');

    $seasonId = Season::currentId();
    assertTrue($seasonId > 0, 'Nessuna stagione corrente disponibile');

    $setup = null;
    foreach (['11', '7', '5'] as $footballType) {
        $competitionStmt = $db->prepare("
            SELECT c.id
            FROM competitions c
            JOIN competition_teams ct ON ct.competition_id = c.id
            WHERE c.season_id = ?
              AND c.football_type = ?
            GROUP BY c.id
            HAVING COUNT(ct.team_id) >= 2
            ORDER BY c.id
            LIMIT 1
        ");
        $competitionStmt->execute([$seasonId, $footballType]);
        $competitionId = (int)$competitionStmt->fetchColumn();

        if ($competitionId <= 0) {
            continue;
        }

        $column = 'can_referee_' . $footballType;
        $refereeStmt = $db->query("
            SELECT id
            FROM referees
            WHERE {$column} = 1
            ORDER BY rating DESC, id
            LIMIT 2
        ");
        $referees = array_map('intval', array_column($refereeStmt->fetchAll(), 'id'));

        if (count($referees) < 2) {
            continue;
        }

        $teamStmt = $db->prepare("
            SELECT team_id
            FROM competition_teams
            WHERE competition_id = ?
            ORDER BY team_id
            LIMIT 2
        ");
        $teamStmt->execute([$competitionId]);
        $teams = array_map('intval', array_column($teamStmt->fetchAll(), 'team_id'));

        if (count($teams) < 2) {
            continue;
        }

        $setup = [
            'football_type' => $footballType,
            'competition_id' => $competitionId,
            'referees' => $referees,
            'teams' => $teams
        ];
        break;
    }

    assertTrue($setup !== null, 'Dati insufficienti: servono una competizione con 2 squadre e 2 arbitri abilitati');

    $db->exec("DELETE FROM designations");
    $db->exec("DELETE FROM referee_team_blacklist");
    $db->exec("DELETE FROM referee_availabilities");

    $validErrors = RefereeAvailabilityValidator::validate([
        'referee_id' => $setup['referees'][0],
        'type' => 'recurring',
        'weekday' => 1,
        'start_time' => '19:00',
        'end_time' => '23:59',
        'is_available' => 1
    ]);
    assertTrue($validErrors === [], 'Validator non accetta una disponibilita ricorrente valida');

    $invalidErrors = RefereeAvailabilityValidator::validate([
        'referee_id' => $setup['referees'][0],
        'type' => 'recurring',
        'weekday' => 1,
        'start_time' => '23:00',
        'end_time' => '19:00',
        'is_available' => 1
    ]);
    assertTrue(isset($invalidErrors['end_time']), 'Validator non blocca intervallo orario invertito');
    pass('validator disponibilita');

    RefereeAvailability::create([
        'referee_id' => $setup['referees'][0],
        'type' => 'recurring',
        'weekday' => 1,
        'start_time' => '19:00',
        'end_time' => '23:59',
        'is_available' => 1,
        'notes' => 'Ricorrente smoke'
    ]);

    assertTrue(
        RefereeAvailability::isAvailableForSlot($setup['referees'][0], '2026-05-04', '20:30:00'),
        'Disponibilita ricorrente non riconosciuta'
    );
    assertTrue(
        !RefereeAvailability::isAvailableForSlot($setup['referees'][0], '2026-05-04', '18:30:00'),
        'Disponibilita fuori fascia erroneamente riconosciuta'
    );
    pass('disponibilita ricorrente');

    RefereeAvailability::create([
        'referee_id' => $setup['referees'][0],
        'type' => 'specific',
        'available_date' => '2026-05-04',
        'start_time' => '19:00',
        'end_time' => '23:59',
        'is_available' => 0,
        'notes' => 'Indisponibile smoke'
    ]);

    assertTrue(
        !RefereeAvailability::isAvailableForSlot($setup['referees'][0], '2026-05-04', '20:30:00'),
        'Indisponibilita puntuale non sovrascrive la ricorrenza'
    );
    pass('indisponibilita puntuale');

    RefereeAvailability::create([
        'referee_id' => $setup['referees'][1],
        'type' => 'specific',
        'available_date' => '2026-05-04',
        'start_time' => '20:00',
        'end_time' => '21:30',
        'is_available' => 1,
        'notes' => 'Disponibile smoke'
    ]);

    $insertMatch = $db->prepare("
        INSERT INTO matches
            (
                season_id,
                competition_id,
                match_day,
                difficulty_rating,
                home_team_id,
                away_team_id,
                match_date,
                match_time,
                status,
                result_type
            )
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', 'played')
    ");
    $insertMatch->execute([
        $seasonId,
        $setup['competition_id'],
        902,
        4,
        $setup['teams'][0],
        $setup['teams'][1],
        '2026-05-04',
        '20:30:00'
    ]);
    $matchId = (int)$db->lastInsertId();

    $generated = Designation::generateProposals([
        'football_type' => $setup['football_type'],
        'competition_id' => $setup['competition_id'],
        'match_day' => 902
    ], $userId);

    assertTrue($generated['generated'] === 1, 'Generazione proposta con disponibilita non riuscita');

    $proposal = Designation::findByMatch($matchId);
    assertTrue($proposal !== null, 'Proposta disponibilita non trovata');
    assertTrue((int)$proposal['referee_id'] === $setup['referees'][1], 'Proposta non rispetta disponibilita arbitro');
    pass('generazione rispetta disponibilita');

    $matches = Designation::attachAvailability(
        Designation::matches([
            'football_type' => $setup['football_type'],
            'competition_id' => $setup['competition_id'],
            'match_day' => 902
        ]),
        Designation::refereeOptions($setup['football_type'])
    );

    $match = null;
    foreach ($matches as $candidate) {
        if ((int)$candidate['match_id'] === $matchId) {
            $match = $candidate;
            break;
        }
    }

    assertTrue($match !== null, 'Partita non trovata in attachAvailability');
    assertTrue(in_array($setup['referees'][1], array_map('intval', $match['available_referee_ids']), true), 'Arbitro disponibile non presente in available_referee_ids');
    assertTrue(in_array($setup['referees'][0], array_map('intval', $match['unavailable_referee_ids']), true), 'Arbitro indisponibile non presente in unavailable_referee_ids');
    pass('payload disponibilita frontend');

    $db->rollBack();
    echo "[OK] Referee availabilities smoke test completed with rollback" . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, "[FAIL] " . $e->getMessage() . PHP_EOL);
    exit(1);
}
