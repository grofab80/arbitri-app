<?php

define('ABSPATH', __DIR__);

function maybe_unserialize($value)
{
    return $value;
}

function apply_filters($hook, $value)
{
    return $value;
}

function remove_accents($value)
{
    return $value;
}

function current_time($format)
{
    return $format === 'Y-m-d' ? '2025-10-01' : date($format);
}

function wp_json_encode($value)
{
    return json_encode($value, JSON_UNESCAPED_SLASHES);
}

function is_ssl()
{
    return true;
}

function home_url($path = '')
{
    return 'https://example.test' . $path;
}

function esc_url_raw($url)
{
    return $url;
}

function assertTrue($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function pass($message)
{
    echo '[PASS] ' . $message . PHP_EOL;
}

require __DIR__ . '/../../integrations/wordpress/football-sync-api/includes/class-football-sync-normalizer.php';

try {
    $normalizer = new Football_Sync_Normalizer();

    $seasons = $normalizer->seasons(array(
        'competitions' => array(array(
            'competition_id' => 4,
            'season_ids' => '8,7',
            'season_text' => '2025/2026,2024/2025',
        )),
        'match_season_ids' => array(8),
    ));
    assertTrue(count($seasons) === 2, 'Numero stagioni normalizzate non corretto');
    assertTrue($seasons[0]['name'] === '2025/2026', 'Nome stagione non mantenuto');
    assertTrue($seasons[0]['is_current'] === true, 'Stagione corrente non rilevata');
    pass('stagioni dedotte da competition e match');

    $competition = $normalizer->competition(array(
        'competition_id' => 4,
        'title' => 'Super League Oro C11',
        'post_name' => 'super-league-oro-c11',
        'league_id' => 12,
        'league_text' => 'Calcio a 11',
        'season_ids' => '8',
        'type' => 'round-robin',
        'format_robin' => 'yes',
        'format_knockout' => '',
        'logo' => '/uploads/logo.png',
        'logo_big' => '',
    ));
    assertTrue($competition['type'] === 'league', 'Tipo competizione non corretto');
    assertTrue($competition['football_type'] === '11', 'Disciplina competizione non dedotta');
    assertTrue($competition['league_external_id'] === 12, 'ID lega non esposto');
    assertTrue($competition['league_name'] === 'Calcio a 11', 'Nome lega non esposto');
    assertTrue($competition['season_external_id'] === 8, 'Stagione competizione non mappata');
    pass('competizione e disciplina');

    $calcioSeven = $normalizer->competition(array(
        'competition_id' => 5,
        'title' => 'Calcio 7 2025-2026 - Fasi Finali',
        'post_name' => 'calcio-7-fasi-finali',
        'league_id' => 13,
        'league_text' => 'Finali',
        'season_ids' => '8',
        'type' => 'knockout',
        'format_robin' => '',
        'format_knockout' => 'yes',
        'logo' => '',
        'logo_big' => '',
    ));
    assertTrue($calcioSeven['football_type'] === '7', 'Forma "Calcio 7" non riconosciuta');

    $coppaC5 = $normalizer->competition(array(
        'competition_id' => 6,
        'title' => 'CoppAca5',
        'post_name' => 'coppaca5',
        'league_id' => 14,
        'league_text' => '',
        'season_ids' => '8',
        'type' => 'knockout',
        'format_robin' => '',
        'format_knockout' => 'yes',
        'logo' => '',
        'logo_big' => '',
    ));
    assertTrue($coppaC5['football_type'] === '5', 'Forma "CoppAca5" non riconosciuta');
    pass('varianti disciplina usate dal sito');

    $refereeA = $normalizer->referee('Mario Rossi');
    $refereeB = $normalizer->referee('Mario Rossi');
    assertTrue($refereeA['external_id'] === $refereeB['external_id'], 'ID arbitro non deterministico');
    assertTrue($refereeA['source'] === 'matches', 'Sorgente arbitro non dichiarata');
    pass('arbitro dedotto con ID stabile');

    $match = $normalizer->match(array(
        'match_id' => 125,
        'season_id' => 8,
        'competition_id' => 4,
        'match_week' => 12,
        'priority' => 4,
        'kickoff' => '2026-03-10 21:00:00',
        'finished' => 1,
        'home_club' => 18,
        'away_club' => 22,
        'home_goals' => 3,
        'away_goals' => 1,
        'special_status' => '',
        'stadium_id' => 6,
        'referee' => 'Mario Rossi',
        'attendance' => 512,
        'aggtext' => '',
    ));
    assertTrue($match['status'] === 'played', 'Stato partita non corretto');
    assertTrue($match['match_time'] === '21:00', 'Orario partita non normalizzato');
    assertTrue($match['referee_external_id'] === $refereeA['external_id'], 'Mapping arbitro partita non coerente');
    assertTrue(strlen($match['hash']) === 64, 'Hash partita non valido');
    pass('partita e hash stabile');

    echo '[OK] WordPress Football Sync normalizer smoke test completed' . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, '[FAIL] ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
