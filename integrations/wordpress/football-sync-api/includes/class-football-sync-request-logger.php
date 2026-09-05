<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Request_Logger
{
    private $starts = array();

    public function register_hooks()
    {
        add_filter('rest_pre_dispatch', array($this, 'start'), 10, 3);
        add_filter('rest_post_dispatch', array($this, 'finish'), 10, 3);
    }

    public function start($result, $server, WP_REST_Request $request)
    {
        if ($this->is_sync_route($request->get_route())) {
            $this->starts[spl_object_hash($request)] = microtime(true);
        }

        return $result;
    }

    public function finish($response, $server, WP_REST_Request $request)
    {
        if (!$this->is_sync_route($request->get_route())) {
            return $response;
        }

        $key = spl_object_hash($request);
        $started_at = isset($this->starts[$key]) ? $this->starts[$key] : microtime(true);
        unset($this->starts[$key]);

        $response = rest_ensure_response($response);
        $request_id = wp_generate_uuid4();
        $response->header('X-Football-Sync-Request-ID', $request_id);
        $this->write_log($request, $response, $request_id, $started_at);
        $this->maybe_prune();

        return $response;
    }

    private function write_log(WP_REST_Request $request, WP_REST_Response $response, $request_id, $started_at)
    {
        global $wpdb;

        $data = $response->get_data();
        $response_count = is_array($data) && isset($data['count']) ? (int) $data['count'] : null;
        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $client_hash = $client_ip !== '' ? hash_hmac('sha256', $client_ip, wp_salt('auth')) : null;

        $wpdb->insert(
            $wpdb->prefix . 'football_sync_logs',
            array(
                'request_id' => $request_id,
                'endpoint' => substr($request->get_route(), 0, 100),
                'method' => substr($request->get_method(), 0, 10),
                'status_code' => (int) $response->get_status(),
                'duration_ms' => max(0, (int) round((microtime(true) - $started_at) * 1000)),
                'response_count' => $response_count,
                'client_hash' => $client_hash,
                'created_at' => gmdate('Y-m-d H:i:s'),
            ),
            array('%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s')
        );
    }

    private function maybe_prune()
    {
        if (get_transient('football_sync_log_pruned')) {
            return;
        }

        global $wpdb;
        $days = max(1, min(365, (int) get_option('football_sync_api_log_retention_days', 30)));
        $threshold = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        $table = $wpdb->prefix . 'football_sync_logs';
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $threshold));
        set_transient('football_sync_log_pruned', 1, DAY_IN_SECONDS);
    }

    private function is_sync_route($route)
    {
        return strpos((string) $route, '/football-sync/v1/') === 0;
    }
}
