<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Repository
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function table_name($suffix)
    {
        return $this->wpdb->prefix . 'anwpfl_' . $suffix;
    }

    public function table_exists($suffix)
    {
        $table = $this->table_name($suffix);
        $found = $this->wpdb->get_var(
            $this->wpdb->prepare('SHOW TABLES LIKE %s', $this->wpdb->esc_like($table))
        );

        return $found === $table;
    }

    public function health()
    {
        $required = array('competitions', 'clubs', 'matches', 'post_entities');
        $tables = array();

        foreach ($required as $suffix) {
            $tables[$suffix] = $this->table_exists($suffix);
        }

        return $tables;
    }

    public function get_competitions(array $filters)
    {
        $table = $this->table_name('competitions');
        $matches = $this->table_name('matches');
        $where = array('1 = 1');
        $params = array();

        if (!empty($filters['id'])) {
            $where[] = 'c.competition_id = %d';
            $params[] = (int) $filters['id'];
        }

        if (!empty($filters['season'])) {
            $where[] = "(FIND_IN_SET(%d, REPLACE(c.season_ids, ' ', '')) > 0 OR EXISTS (SELECT 1 FROM {$matches} m WHERE m.competition_id = c.competition_id AND m.season_id = %d))";
            $params[] = (int) $filters['season'];
            $params[] = (int) $filters['season'];
        }

        $where_sql = implode(' AND ', $where);
        $count_sql = "SELECT COUNT(*) FROM {$table} c WHERE {$where_sql}";
        $data_sql = "SELECT c.* FROM {$table} c WHERE {$where_sql} ORDER BY c.competition_order, c.title, c.competition_id LIMIT %d OFFSET %d";

        $count = (int) $this->wpdb->get_var($this->prepare($count_sql, $params));
        $data_params = array_merge($params, array($filters['limit'], $filters['offset']));
        $rows = $this->wpdb->get_results($this->prepare($data_sql, $data_params), ARRAY_A);

        return array('rows' => $rows, 'total' => $count);
    }

    public function get_teams(array $filters)
    {
        $table = $this->table_name('clubs');
        $matches = $this->table_name('matches');
        $where = array('1 = 1');
        $params = array();

        if (!empty($filters['id'])) {
            $where[] = 'c.club_id = %d';
            $params[] = (int) $filters['id'];
        }

        if (!empty($filters['competition'])) {
            $where[] = "EXISTS (SELECT 1 FROM {$matches} m WHERE m.competition_id = %d AND (m.home_club = c.club_id OR m.away_club = c.club_id))";
            $params[] = (int) $filters['competition'];
        }

        if (!empty($filters['season'])) {
            $where[] = "EXISTS (SELECT 1 FROM {$matches} m WHERE m.season_id = %d AND (m.home_club = c.club_id OR m.away_club = c.club_id))";
            $params[] = (int) $filters['season'];
        }

        $where_sql = implode(' AND ', $where);
        $count_sql = "SELECT COUNT(*) FROM {$table} c WHERE {$where_sql}";
        $data_sql = "SELECT c.* FROM {$table} c WHERE {$where_sql} ORDER BY c.title, c.club_id LIMIT %d OFFSET %d";

        $count = (int) $this->wpdb->get_var($this->prepare($count_sql, $params));
        $data_params = array_merge($params, array($filters['limit'], $filters['offset']));
        $rows = $this->wpdb->get_results($this->prepare($data_sql, $data_params), ARRAY_A);

        return array('rows' => $rows, 'total' => $count);
    }

    public function get_matches(array $filters)
    {
        $table = $this->table_name('matches');
        $where = array('1 = 1');
        $params = array();

        $numeric_filters = array(
            'id' => 'm.match_id',
            'competition' => 'm.competition_id',
            'season' => 'm.season_id',
            'stadium' => 'm.stadium_id',
        );

        foreach ($numeric_filters as $filter => $column) {
            if (!empty($filters[$filter])) {
                $where[] = "{$column} = %d";
                $params[] = (int) $filters[$filter];
            }
        }

        if (!empty($filters['team'])) {
            $where[] = '(m.home_club = %d OR m.away_club = %d)';
            $params[] = (int) $filters['team'];
            $params[] = (int) $filters['team'];
        }

        if (!empty($filters['referee_name'])) {
            $where[] = 'm.referee = %s';
            $params[] = $filters['referee_name'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(m.kickoff) >= %s';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(m.kickoff) <= %s';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['status'])) {
            $this->add_status_filter($filters['status'], $where, $params);
        }

        $where_sql = implode(' AND ', $where);
        $count_sql = "SELECT COUNT(*) FROM {$table} m WHERE {$where_sql}";
        $data_sql = "SELECT m.* FROM {$table} m WHERE {$where_sql} ORDER BY m.kickoff, m.match_id LIMIT %d OFFSET %d";

        $count = (int) $this->wpdb->get_var($this->prepare($count_sql, $params));
        $data_params = array_merge($params, array($filters['limit'], $filters['offset']));
        $rows = $this->wpdb->get_results($this->prepare($data_sql, $data_params), ARRAY_A);

        return array('rows' => $rows, 'total' => $count);
    }

    public function get_season_sources()
    {
        $competitions = $this->table_name('competitions');
        $matches = $this->table_name('matches');

        return array(
            'competitions' => $this->wpdb->get_results(
                "SELECT competition_id, season_ids, season_text FROM {$competitions} ORDER BY competition_id",
                ARRAY_A
            ),
            'match_season_ids' => $this->wpdb->get_col(
                "SELECT DISTINCT season_id FROM {$matches} WHERE season_id > 0 ORDER BY season_id"
            ),
        );
    }

    public function get_stadium_ids()
    {
        $clubs = $this->table_name('clubs');
        $matches = $this->table_name('matches');
        $sql = "SELECT stadium_id FROM {$clubs} WHERE stadium_id > 0 UNION SELECT stadium_id FROM {$matches} WHERE stadium_id > 0 ORDER BY stadium_id";

        return array_map('intval', $this->wpdb->get_col($sql));
    }

    public function get_referee_names()
    {
        $matches = $this->table_name('matches');
        $sql = "SELECT DISTINCT TRIM(referee) FROM {$matches} WHERE TRIM(referee) <> '' ORDER BY TRIM(referee)";

        return array_values(array_filter(array_map('strval', $this->wpdb->get_col($sql))));
    }

    public function get_referee_name_by_external_id($external_id)
    {
        foreach ($this->get_referee_names() as $name) {
            if ((string) Football_Sync_Normalizer::referee_external_id($name) === (string) $external_id) {
                return $name;
            }
        }

        return null;
    }

    public function get_team_competition_map(array $team_ids)
    {
        if (!$team_ids) {
            return array();
        }

        $matches = $this->table_name('matches');
        $placeholders = implode(',', array_fill(0, count($team_ids), '%d'));
        $sql = "SELECT home_club AS team_id, competition_id FROM {$matches} WHERE home_club IN ({$placeholders}) UNION SELECT away_club AS team_id, competition_id FROM {$matches} WHERE away_club IN ({$placeholders})";
        $params = array_merge($team_ids, $team_ids);
        $rows = $this->wpdb->get_results($this->prepare($sql, $params), ARRAY_A);
        $map = array();

        foreach ($rows as $row) {
            $team_id = (int) $row['team_id'];
            $map[$team_id][] = (int) $row['competition_id'];
        }

        foreach ($map as $team_id => $competition_ids) {
            $map[$team_id] = array_values(array_unique($competition_ids));
            sort($map[$team_id]);
        }

        return $map;
    }

    public function get_modified_map($entity_type, array $entity_ids)
    {
        if (!$entity_ids || !$this->table_exists('post_entities')) {
            return array();
        }

        $entities = $this->table_name('post_entities');
        $posts = $this->wpdb->posts;
        $id_placeholders = implode(',', array_fill(0, count($entity_ids), '%d'));
        $aliases = $this->entity_aliases($entity_type);
        $alias_placeholders = implode(',', array_fill(0, count($aliases), '%s'));
        $sql = "SELECT pe.entity_id, MAX(p.post_modified) AS updated_at FROM {$entities} pe JOIN {$posts} p ON p.ID = pe.post_id WHERE pe.entity_id IN ({$id_placeholders}) AND LOWER(pe.entity_type) IN ({$alias_placeholders}) GROUP BY pe.entity_id";
        $params = array_merge($entity_ids, $aliases);
        $rows = $this->wpdb->get_results($this->prepare($sql, $params), ARRAY_A);
        $map = array();

        foreach ($rows as $row) {
            $map[(int) $row['entity_id']] = $this->valid_datetime($row['updated_at']);
        }

        return $map;
    }

    public function get_stadium_posts(array $stadium_ids)
    {
        if (!$stadium_ids) {
            return array();
        }

        $posts = $this->wpdb->posts;
        $postmeta = $this->wpdb->postmeta;
        $placeholders = implode(',', array_fill(0, count($stadium_ids), '%d'));
        $post_sql = "SELECT ID, post_title, post_modified FROM {$posts} WHERE ID IN ({$placeholders})";
        $meta_sql = "SELECT post_id, meta_key, meta_value FROM {$postmeta} WHERE post_id IN ({$placeholders})";
        $post_rows = $this->wpdb->get_results($this->prepare($post_sql, $stadium_ids), ARRAY_A);
        $meta_rows = $this->wpdb->get_results($this->prepare($meta_sql, $stadium_ids), ARRAY_A);
        $result = array();

        foreach ($post_rows as $row) {
            $result[(int) $row['ID']] = array(
                'name' => $row['post_title'],
                'updated_at' => $this->valid_datetime($row['post_modified']),
                'meta' => array(),
            );
        }

        foreach ($meta_rows as $row) {
            $post_id = (int) $row['post_id'];
            if (!isset($result[$post_id])) {
                $result[$post_id] = array('name' => '', 'updated_at' => null, 'meta' => array());
            }
            $result[$post_id]['meta'][(string) $row['meta_key']] = maybe_unserialize($row['meta_value']);
        }

        return $result;
    }

    private function add_status_filter($status, array &$where, array &$params)
    {
        $cancel_markers = array('%cancel%', '%annull%', '%abandon%');

        if ($status === 'played') {
            $where[] = 'm.finished = 1';
        } elseif ($status === 'cancelled') {
            $where[] = '(LOWER(m.special_status) LIKE %s OR LOWER(m.special_status) LIKE %s OR LOWER(m.special_status) LIKE %s)';
            $params = array_merge($params, $cancel_markers);
        } elseif ($status === 'scheduled') {
            $where[] = 'm.finished = 0 AND LOWER(m.special_status) NOT LIKE %s AND LOWER(m.special_status) NOT LIKE %s AND LOWER(m.special_status) NOT LIKE %s';
            $params = array_merge($params, $cancel_markers);
        }
    }

    private function entity_aliases($entity_type)
    {
        $aliases = array(
            'competition' => array('competition', 'competitions'),
            'team' => array('club', 'team', 'clubs'),
            'match' => array('match', 'matches'),
            'season' => array('season', 'seasons'),
            'stadium' => array('stadium', 'stadiums'),
        );

        return isset($aliases[$entity_type]) ? $aliases[$entity_type] : array(strtolower($entity_type));
    }

    private function prepare($sql, array $params)
    {
        return $params ? $this->wpdb->prepare($sql, $params) : $sql;
    }

    private function valid_datetime($value)
    {
        if (!$value || strpos($value, '0000-00-00') === 0) {
            return null;
        }

        return (string) $value;
    }
}
