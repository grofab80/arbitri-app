<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_REST_Controller
{
    private const NAMESPACE = 'football-sync/v1';

    private $repository;
    private $catalog;
    private $auth;
    private $cache;
    private $sync;
    private $index;

    public function __construct(
        Football_Sync_Repository $repository,
        Football_Sync_Catalog_Service $catalog,
        Football_Sync_Auth $auth,
        Football_Sync_Cache $cache,
        Football_Sync_Service $sync,
        Football_Sync_Index_Repository $index
    ) {
        $this->repository = $repository;
        $this->catalog = $catalog;
        $this->auth = $auth;
        $this->cache = $cache;
        $this->sync = $sync;
        $this->index = $index;
    }

    public function register_hooks()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        $this->register_get_route('/info', 'info');
        $this->register_get_route('/seasons', 'seasons', $this->list_args());
        $this->register_get_route('/competitions', 'competitions', array_merge($this->list_args(), array(
            'season' => $this->integer_arg(),
        )));
        $this->register_get_route('/teams', 'teams', array_merge($this->list_args(), array(
            'competition' => $this->integer_arg(),
            'season' => $this->integer_arg(),
        )));
        $this->register_get_route('/stadiums', 'stadiums', $this->list_args());
        $this->register_get_route('/referees', 'referees', $this->list_args());
        $this->register_get_route('/matches', 'matches', array_merge($this->list_args(), array(
            'competition' => $this->integer_arg(),
            'season' => $this->integer_arg(),
            'team' => $this->integer_arg(),
            'stadium' => $this->integer_arg(),
            'referee' => $this->integer_arg(),
            'status' => array(
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => static function ($value) {
                    return in_array($value, array('scheduled', 'played', 'cancelled'), true);
                },
            ),
            'date_from' => $this->date_arg(),
            'date_to' => $this->date_arg(),
        )));
        $this->register_get_route('/sync', 'sync', array(
            'updated_after' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => static function ($value) {
                    return is_string($value) && trim($value) !== '' && strtotime($value) !== false;
                },
            ),
            'refresh' => array(
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ),
        ));
    }

    public function info(WP_REST_Request $request)
    {
        $auth_error = $this->auth_error($request);
        if ($auth_error) {
            return $auth_error;
        }

        $tables = $this->repository->health();
        $database_ok = !in_array(false, $tables, true) && $this->index->is_ready();
        $anwp_version = defined('ANWPFL_VERSION')
            ? ANWPFL_VERSION
            : get_option('anwpfl_version', null);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'plugin' => 'Football Sync API',
                'version' => FOOTBALL_SYNC_API_VERSION,
                'wordpress' => get_bloginfo('version'),
                'anwp' => $anwp_version ?: null,
                'database' => $database_ok ? 'OK' : 'MISSING_TABLES',
                'tables' => $tables,
                'timezone' => wp_timezone_string(),
                'incremental_sync' => true,
                'sync_index_ready' => $this->index->is_ready(),
                'last_indexed_at' => $this->index->last_indexed_at(),
                'index_stats' => $this->index->stats(),
                'cache_ttl_seconds' => $this->cache->ttl(),
                'log_retention_days' => (int) get_option('football_sync_api_log_retention_days', 30),
            ),
        ), $database_ok ? 200 : 503);
    }

    public function seasons(WP_REST_Request $request)
    {
        return $this->run_list($request, 'seasons', array('competitions', 'matches'));
    }

    public function competitions(WP_REST_Request $request)
    {
        return $this->run_list($request, 'competitions', array('competitions', 'matches'));
    }

    public function teams(WP_REST_Request $request)
    {
        return $this->run_list($request, 'teams', array('clubs', 'matches'));
    }

    public function stadiums(WP_REST_Request $request)
    {
        return $this->run_list($request, 'stadiums', array('clubs', 'matches'));
    }

    public function referees(WP_REST_Request $request)
    {
        return $this->run_list($request, 'referees', array('matches'));
    }

    public function matches(WP_REST_Request $request)
    {
        return $this->run_list($request, 'matches', array('matches'));
    }

    public function sync(WP_REST_Request $request)
    {
        $auth_error = $this->auth_error($request);
        if ($auth_error) {
            return $auth_error;
        }

        foreach (array('competitions', 'clubs', 'matches') as $table) {
            if (!$this->repository->table_exists($table)) {
                return $this->error('Tabella AnWP non disponibile: ' . $table, 'ANWP_TABLE_MISSING', 503);
            }
        }

        try {
            $updated_after = $this->normalize_timestamp($request->get_param('updated_after'));
            $refresh = rest_sanitize_boolean($request->get_param('refresh'));
            $result = $this->sync->execute($updated_after, $refresh);

            return new WP_REST_Response(array_merge(array('success' => true), $result), 200);
        } catch (Throwable $exception) {
            do_action('football_sync_api_error', $exception);
            $locked = strpos($exception->getMessage(), 'gia in esecuzione') !== false;

            return $this->error(
                $locked ? $exception->getMessage() : 'Errore durante l aggiornamento dell indice sync.',
                $locked ? 'SYNC_ALREADY_RUNNING' : 'SYNC_INDEX_ERROR',
                $locked ? 409 : 500
            );
        }
    }

    private function run_list(WP_REST_Request $request, $entity_type, array $required_tables)
    {
        $auth_error = $this->auth_error($request);
        if ($auth_error) {
            return $auth_error;
        }

        if ($request->get_param('updated_after')) {
            return $this->error(
                'Usare GET /sync per individuare i record modificati e richiamare questo endpoint tramite id.',
                'USE_SYNC_ENDPOINT',
                400
            );
        }

        foreach ($required_tables as $table) {
            if (!$this->repository->table_exists($table)) {
                return $this->error('Tabella AnWP non disponibile: ' . $table, 'ANWP_TABLE_MISSING', 503);
            }
        }

        try {
            $filters = $this->filters($request);
            $force = rest_sanitize_boolean($request->get_param('refresh'));
            $result = $this->cache->remember(
                'list_' . $entity_type,
                $filters,
                function () use ($entity_type, $filters) {
                    return $this->catalog->page($entity_type, $filters);
                },
                $force
            );

            return $this->list_response($result['records'], $result['total'], $filters);
        } catch (Throwable $exception) {
            do_action('football_sync_api_error', $exception);
            return $this->error('Errore durante la lettura dei dati AnWP.', 'ANWP_READ_ERROR', 500);
        }
    }

    private function auth_error(WP_REST_Request $request)
    {
        $authorized = $this->auth->authorize($request);
        if (!is_wp_error($authorized)) {
            return null;
        }

        $data = $authorized->get_error_data();
        $status = isset($data['status']) ? (int) $data['status'] : 401;

        return $this->error($authorized->get_error_message(), strtoupper($authorized->get_error_code()), $status);
    }

    private function list_response(array $records, $total, array $filters)
    {
        $last_update = null;
        foreach ($records as $record) {
            if (!empty($record['updated_at']) && (!$last_update || $record['updated_at'] > $last_update)) {
                $last_update = $record['updated_at'];
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'count' => count($records),
            'total' => (int) $total,
            'page' => $filters['page'],
            'limit' => $filters['limit'],
            'has_more' => ($filters['page'] * $filters['limit']) < (int) $total,
            'last_update' => $last_update,
            'data' => $records,
        ), 200);
    }

    private function filters(WP_REST_Request $request)
    {
        $filters = $this->catalog->default_filters();
        $page = max(1, (int) $request->get_param('page'));
        $limit = min(500, max(1, (int) ($request->get_param('limit') ?: 100)));

        $filters['id'] = (int) $request->get_param('id');
        $filters['page'] = $page;
        $filters['limit'] = $limit;
        $filters['offset'] = ($page - 1) * $limit;
        $filters['competition'] = (int) $request->get_param('competition');
        $filters['season'] = (int) $request->get_param('season');
        $filters['team'] = (int) $request->get_param('team');
        $filters['stadium'] = (int) $request->get_param('stadium');
        $filters['referee'] = (int) $request->get_param('referee');
        $filters['status'] = sanitize_key((string) $request->get_param('status'));
        $filters['date_from'] = sanitize_text_field((string) $request->get_param('date_from'));
        $filters['date_to'] = sanitize_text_field((string) $request->get_param('date_to'));

        return $filters;
    }

    private function normalize_timestamp($value)
    {
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            throw new InvalidArgumentException('updated_after non valido.');
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function register_get_route($route, $method, array $args = array())
    {
        register_rest_route(self::NAMESPACE, $route, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, $method),
            'permission_callback' => '__return_true',
            'args' => $args,
        ));
    }

    private function list_args()
    {
        return array(
            'id' => $this->integer_arg(),
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => static function ($value) {
                    return (int) $value >= 1;
                },
            ),
            'limit' => array(
                'default' => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => static function ($value) {
                    return (int) $value >= 1 && (int) $value <= 500;
                },
            ),
            'updated_after' => array('sanitize_callback' => 'sanitize_text_field'),
            'refresh' => array(
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ),
        );
    }

    private function integer_arg()
    {
        return array(
            'sanitize_callback' => 'absint',
            'validate_callback' => static function ($value) {
                return $value === null || $value === '' || (int) $value > 0;
            },
        );
    }

    private function date_arg()
    {
        return array(
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => static function ($value) {
                return $value === null || $value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
            },
        );
    }

    private function error($message, $code, $status)
    {
        return new WP_REST_Response(array(
            'success' => false,
            'error' => $message,
            'code' => $code,
        ), $status);
    }
}
