<?php
/**
 * Plugin Name: Football Sync API
 * Description: Espone i dati AnWP Football Leagues tramite un contratto REST stabile per arbitri-app.
 * Version: 0.2.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Arbitri App
 * Text Domain: football-sync-api
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FOOTBALL_SYNC_API_VERSION', '0.2.1');
define('FOOTBALL_SYNC_API_FILE', __FILE__);
define('FOOTBALL_SYNC_API_DIR', plugin_dir_path(__FILE__));

require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-auth.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-installer.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-repository.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-normalizer.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-catalog-service.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-cache.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-index-repository.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-service.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-request-logger.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-rest-controller.php';
require_once FOOTBALL_SYNC_API_DIR . 'includes/class-football-sync-settings.php';

register_activation_hook(__FILE__, array('Football_Sync_Installer', 'activate'));

add_action('plugins_loaded', static function () {
    Football_Sync_Installer::maybe_upgrade();

    $repository = new Football_Sync_Repository();
    $normalizer = new Football_Sync_Normalizer();
    $auth = new Football_Sync_Auth();
    $catalog = new Football_Sync_Catalog_Service($repository, $normalizer);
    $cache = new Football_Sync_Cache();
    $index = new Football_Sync_Index_Repository();
    $sync = new Football_Sync_Service($catalog, $index, $cache);

    (new Football_Sync_REST_Controller($repository, $catalog, $auth, $cache, $sync, $index))->register_hooks();
    $cache->register_hooks();
    (new Football_Sync_Request_Logger())->register_hooks();
    (new Football_Sync_Settings())->register_hooks();
});
