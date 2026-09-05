<?php

define('ABSPATH', __DIR__);

$GLOBALS['football_sync_test_options'] = array(
    'football_sync_cache_generation' => 1,
    'football_sync_api_cache_ttl' => 300,
    'football_sync_last_indexed_at' => null,
);
$GLOBALS['football_sync_test_transients'] = array();

function add_action()
{
    return true;
}

function get_option($key, $default = false)
{
    return array_key_exists($key, $GLOBALS['football_sync_test_options'])
        ? $GLOBALS['football_sync_test_options'][$key]
        : $default;
}

function update_option($key, $value)
{
    $GLOBALS['football_sync_test_options'][$key] = $value;
    return true;
}

function get_transient($key)
{
    return array_key_exists($key, $GLOBALS['football_sync_test_transients'])
        ? $GLOBALS['football_sync_test_transients'][$key]
        : false;
}

function set_transient($key, $value)
{
    $GLOBALS['football_sync_test_transients'][$key] = $value;
    return true;
}

function apply_filters($hook, $value)
{
    return $value;
}

function wp_json_encode($value)
{
    return json_encode($value);
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

require __DIR__ . '/../../integrations/wordpress/football-sync-api/includes/class-football-sync-cache.php';
require __DIR__ . '/../../integrations/wordpress/football-sync-api/includes/class-football-sync-catalog-service.php';
require __DIR__ . '/../../integrations/wordpress/football-sync-api/includes/class-football-sync-index-repository.php';
require __DIR__ . '/../../integrations/wordpress/football-sync-api/includes/class-football-sync-service.php';

try {
    $cache = new Football_Sync_Cache();
    $calls = 0;
    $callback = static function () use (&$calls) {
        $calls++;
        return array('value' => $calls);
    };

    $first = $cache->remember('test', array('page' => 1), $callback);
    $second = $cache->remember('test', array('page' => 1), $callback);
    assertTrue($first === $second && $calls === 1, 'La cache non riusa il valore salvato');
    pass('cache transient riutilizzata');

    $cache->clear();
    $third = $cache->remember('test', array('page' => 1), $callback);
    assertTrue($third['value'] === 2 && $calls === 2, 'Invalidazione generazionale cache non riuscita');
    pass('cache invalidata tramite generazione');

    $catalog = (new ReflectionClass('Football_Sync_Catalog_Service'))->newInstanceWithoutConstructor();
    $index = (new ReflectionClass('Football_Sync_Index_Repository'))->newInstanceWithoutConstructor();
    $service = new Football_Sync_Service($catalog, $index, $cache);
    $method = new ReflectionMethod('Football_Sync_Service', 'next_index_timestamp');
    $method->setAccessible(true);
    $GLOBALS['football_sync_test_options']['football_sync_last_indexed_at'] = '2099-01-01 00:00:00';
    $next = $method->invoke($service);
    assertTrue($next === '2099-01-01 00:00:01', 'Il cursore sync non e monotono');
    pass('cursore sync monotono');

    $index_source = file_get_contents(
        __DIR__ . '/../../integrations/wordpress/football-sync-api/includes/class-football-sync-index-repository.php'
    );
    assertTrue(strpos($index_source, 'changed_at > %s') !== false, 'Confronto updated_after non esclusivo');
    assertTrue(strpos($index_source, 'deleted_at = %s') !== false, 'Tombstone cancellazioni non presente');
    pass('cursore esclusivo e tombstone');

    echo '[OK] WordPress Football Sync phase 3 smoke test completed' . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, '[FAIL] ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
