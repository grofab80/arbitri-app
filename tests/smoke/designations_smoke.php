<?php

require __DIR__ . '/../../api/v1/bootstrap.php';

use Api\Core\Database;
use Api\Models\Designation;
use Api\Models\Season;

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
            LIMIT 3
        ");
        $referees = array_map('intval', array_column($refereeStmt->fetchAll(), 'id'));

        if (count($referees) < 3) {
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

    assertTrue($setup !== null, 'Dati insufficienti: servono una competizione con 2 squadre e 3 arbitri abilitati');

    $db->exec("DELETE FROM designations");
    $db->exec("DELETE FROM referee_team_blacklist");
    $db->exec("DELETE FROM referee_availabilities");

    $insertAvailability = $db->prepare("
        INSERT INTO referee_availabilities
            (referee_id, type, weekday, start_time, end_time, is_available, notes)
        VALUES
            (?, 'recurring', 5, '19:00:00', '23:59:00', 1, 'Smoke test')
    ");

    foreach ($setup['referees'] as $refereeId) {
        $insertAvailability->execute([$refereeId]);
    }

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
        901,
        4,
        $setup['teams'][0],
        $setup['teams'][1],
        '2026-05-01',
        '20:30:00'
    ]);
    $matchA = (int)$db->lastInsertId();

    $insertMatch->execute([
        $seasonId,
        $setup['competition_id'],
        901,
        4,
        $setup['teams'][1],
        $setup['teams'][0],
        '2026-05-01',
        '21:00:00'
    ]);
    $matchB = (int)$db->lastInsertId();

    $manual = Designation::assignManual($matchA, $setup['referees'][0], $userId);
    assertTrue(!empty($manual['success']), 'Modifica manuale non riuscita');

    $manualDesignation = Designation::findByMatch($matchA);
    assertTrue((int)$manualDesignation['referee_id'] === $setup['referees'][0], 'Arbitro manuale non salvato');
    assertTrue($manualDesignation['assignment_type'] === 'manual', 'Assignment type manuale non salvato');
    assertTrue($manualDesignation['status'] === 'modificata', 'Stato modifica manuale non corretto');
    pass('modifica manuale');

    $confirmed = Designation::confirm($matchA, $userId);
    assertTrue(!empty($confirmed['success']), 'Conferma designazione non riuscita');
    assertTrue(Designation::findByMatch($matchA)['status'] === 'confermata', 'Stato confermata non salvato');
    pass('conferma');

    $blacklistId = Designation::addBlacklist($setup['referees'][1], $setup['teams'][1], 'Smoke test');
    assertTrue($blacklistId > 0, 'Blacklist non salvata');
    assertTrue(count(Designation::blacklist()) === 1, 'Blacklist attiva non trovata');
    pass('blacklist add/list');

    $generated = Designation::generateProposals([
        'football_type' => $setup['football_type'],
        'competition_id' => $setup['competition_id'],
        'match_day' => 901
    ], $userId);
    assertTrue($generated['generated'] === 1, 'Generazione proposta non ha prodotto una designazione');

    $proposal = Designation::findByMatch($matchB);
    assertTrue($proposal !== null, 'Proposta non trovata');
    assertTrue($proposal['assignment_type'] === 'auto', 'Assignment type automatico non salvato');
    assertTrue($proposal['status'] === 'proposta', 'Stato proposta non salvato');
    assertTrue((int)$proposal['referee_id'] !== $setup['referees'][0], 'Conflitto orario non rispettato');
    assertTrue((int)$proposal['referee_id'] !== $setup['referees'][1], 'Blacklist non rispettata');
    pass('generazione e conflitti');

    $cleared = Designation::clearAutomaticProposals([
        'football_type' => $setup['football_type'],
        'competition_id' => $setup['competition_id'],
        'match_day' => 901
    ]);
    assertTrue($cleared['cleared'] === 1, 'Pulizia proposte automatiche non riuscita');
    assertTrue(Designation::findByMatch($matchB) === null, 'Proposta automatica ancora presente dopo pulizia');

    $regenerated = Designation::regenerateUnconfirmed([
        'football_type' => $setup['football_type'],
        'competition_id' => $setup['competition_id'],
        'match_day' => 901
    ], $userId);
    assertTrue($regenerated['generated'] === 1, 'Rigenerazione proposta non riuscita');
    assertTrue(Designation::findByMatch($matchB)['status'] === 'proposta', 'Proposta rigenerata non valida');

    $bulkConfirmed = Designation::confirmFilteredProposals([
        'football_type' => $setup['football_type'],
        'competition_id' => $setup['competition_id'],
        'match_day' => 901
    ], $userId);
    assertTrue($bulkConfirmed['confirmed'] === 1, 'Conferma massiva non ha confermato la proposta');
    assertTrue(Designation::findByMatch($matchB)['status'] === 'confermata', 'Proposta non confermata massivamente');
    pass('azioni massive');

    assertTrue(Designation::removeBlacklist($blacklistId), 'Rimozione blacklist non riuscita');
    assertTrue(count(Designation::blacklist()) === 0, 'Blacklist disattivata ancora visibile');
    pass('blacklist remove');

    $db->rollBack();
    echo "[OK] Designations smoke test completed with rollback" . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, "[FAIL] " . $e->getMessage() . PHP_EOL);
    exit(1);
}
