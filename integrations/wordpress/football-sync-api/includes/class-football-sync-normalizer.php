<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Normalizer
{
    public function seasons(array $sources)
    {
        $seasons = array();

        foreach ($sources['competitions'] as $competition) {
            $ids = $this->numeric_list($competition['season_ids']);
            $names = $this->text_list($competition['season_text']);

            foreach ($ids as $index => $id) {
                $name = isset($names[$index]) ? $names[$index] : null;
                $this->merge_season($seasons, $id, $name);
            }

            if (!$ids) {
                foreach ($names as $name) {
                    $this->merge_season($seasons, self::stable_numeric_id('season:' . $name), $name);
                }
            }
        }

        foreach ($sources['match_season_ids'] as $id) {
            $this->merge_season($seasons, (int) $id, null);
        }

        $current_id = $this->current_season_id($seasons);
        $records = array();

        foreach ($seasons as $id => $season) {
            $dates = $this->season_dates($season['name']);
            $is_current = (int) $id === $current_id;
            $record = array(
                'external_id' => (int) $id,
                'name' => $season['name'],
                'starts_on' => $dates['starts_on'],
                'ends_on' => $dates['ends_on'],
                'is_current' => $is_current,
                'status' => $this->season_status($dates, $is_current),
                'updated_at' => null,
            );
            $record['hash'] = $this->hash($record);
            $records[] = $record;
        }

        usort($records, static function ($left, $right) {
            return strcmp((string) $right['name'], (string) $left['name']);
        });

        return $records;
    }

    public function competition(array $row, $updated_at = null)
    {
        $season_ids = $this->numeric_list($row['season_ids']);
        $record = array(
            'external_id' => (int) $row['competition_id'],
            'season_external_id' => $season_ids ? max($season_ids) : null,
            'season_external_ids' => $season_ids,
            'name' => $this->nullable_string($row['title']),
            'slug' => $this->nullable_string($row['post_name']),
            'league_external_id' => (int) ($row['league_id'] ?? 0) > 0 ? (int) $row['league_id'] : null,
            'league_name' => $this->nullable_string($row['league_text'] ?? null),
            'type' => $this->competition_type($row),
            'football_type' => $this->football_type(($row['title'] ?? '') . ' ' . ($row['league_text'] ?? ''), $row),
            'logo_url' => $this->absolute_url($row['logo_big'] ?: $row['logo']),
            'status' => 'active',
            'updated_at' => $updated_at,
        );
        $record['hash'] = $this->hash($record);

        return $record;
    }

    public function team(array $row, array $competition_ids, $updated_at = null)
    {
        $details = $this->structured_value($row['club_details']);
        $social = $this->structured_value($row['club_social']);
        $record = array(
            'external_id' => (int) $row['club_id'],
            'name' => $this->nullable_string($row['title']),
            'short_name' => $this->nullable_string($row['abbr']),
            'slug' => $this->nullable_string($row['post_name']),
            'city' => $this->nullable_string($row['city']),
            'country' => $this->nullable_string($row['nationality']),
            'logo_url' => $this->absolute_url($row['logo_big'] ?: $row['logo']),
            'primary_stadium_external_id' => (int) $row['stadium_id'] > 0 ? (int) $row['stadium_id'] : null,
            'competition_external_ids' => array_values(array_map('intval', $competition_ids)),
            'website' => $this->find_value(array($details, $social), array('website', 'site', 'url')),
            'facebook' => $this->find_value(array($social), array('facebook', 'facebook_url')),
            'instagram' => $this->find_value(array($social), array('instagram', 'instagram_url')),
            'updated_at' => $updated_at,
        );
        $record['hash'] = $this->hash($record);

        return $record;
    }

    public function stadium($external_id, array $post = array())
    {
        $meta = isset($post['meta']) && is_array($post['meta']) ? $post['meta'] : array();
        $record = array(
            'external_id' => (int) $external_id,
            'name' => $this->nullable_string(isset($post['name']) ? $post['name'] : null) ?: 'Stadio #' . (int) $external_id,
            'address' => $this->find_value(array($meta), array('address', 'stadium_address', '_anwpfl_address')),
            'city' => $this->find_value(array($meta), array('city', 'stadium_city', '_anwpfl_city')),
            'province' => $this->find_value(array($meta), array('province', 'state', 'stadium_state', '_anwpfl_state')),
            'postal_code' => $this->find_value(array($meta), array('postal_code', 'postcode', 'zip', '_anwpfl_zip')),
            'country' => $this->find_value(array($meta), array('country', 'stadium_country', '_anwpfl_country')) ?: 'Italia',
            'capacity' => $this->nullable_int($this->find_value(array($meta), array('capacity', 'stadium_capacity', '_anwpfl_capacity'))),
            'latitude' => $this->nullable_float($this->find_value(array($meta), array('latitude', 'lat', '_anwpfl_latitude'))),
            'longitude' => $this->nullable_float($this->find_value(array($meta), array('longitude', 'lng', 'lon', '_anwpfl_longitude'))),
            'photo_url' => $this->absolute_url($this->find_value(array($meta), array('photo_url', 'stadium_photo', '_anwpfl_photo'))),
            'can_host_11' => true,
            'can_host_7' => true,
            'can_host_5' => true,
            'updated_at' => isset($post['updated_at']) ? $post['updated_at'] : null,
        );
        $record = apply_filters('football_sync_api_stadium_record', $record, $post);
        $record['hash'] = $this->hash($record);

        return $record;
    }

    public function referee($name)
    {
        $parts = preg_split('/\s+/', trim((string) $name));
        $last_name = count($parts) > 1 ? array_pop($parts) : null;
        $first_name = $parts ? implode(' ', $parts) : (string) $name;
        $record = array(
            'external_id' => self::referee_external_id($name),
            'name' => trim((string) $name),
            'first_name' => $this->nullable_string($first_name),
            'last_name' => $this->nullable_string($last_name),
            'city' => null,
            'country' => 'Italia',
            'can_referee_11' => true,
            'can_referee_7' => true,
            'can_referee_5' => true,
            'source' => 'matches',
            'updated_at' => null,
        );
        $record = apply_filters('football_sync_api_referee_record', $record, $name);
        $record['hash'] = $this->hash($record);

        return $record;
    }

    public function match(array $row, $updated_at = null)
    {
        $kickoff = $this->split_datetime($row['kickoff']);
        $referee_name = $this->nullable_string($row['referee']);
        $status = $this->match_status($row);
        $result_type = $this->result_type($row);
        $record = array(
            'external_id' => (int) $row['match_id'],
            'season_external_id' => (int) $row['season_id'] ?: null,
            'competition_external_id' => (int) $row['competition_id'] ?: null,
            'match_day' => max(1, (int) $row['match_week']),
            'difficulty_rating' => $this->rating($row['priority']),
            'match_date' => $kickoff['date'],
            'match_time' => $kickoff['time'],
            'status' => $status,
            'home_team_external_id' => (int) $row['home_club'] ?: null,
            'away_team_external_id' => (int) $row['away_club'] ?: null,
            'home_goals' => $status === 'played' ? (int) $row['home_goals'] : null,
            'away_goals' => $status === 'played' ? (int) $row['away_goals'] : null,
            'result_type' => $result_type,
            'walkover_reason' => $result_type === 'played' ? null : $this->nullable_string($row['special_status']),
            'field_external_id' => (int) $row['stadium_id'] > 0 ? (int) $row['stadium_id'] : null,
            'referee_external_id' => $referee_name ? self::referee_external_id($referee_name) : null,
            'referee_name' => $referee_name,
            'attendance' => (int) $row['attendance'] > 0 ? (int) $row['attendance'] : null,
            'youtube_url' => null,
            'highlights_url' => null,
            'notes' => $this->nullable_string($row['aggtext']),
            'updated_at' => $updated_at,
        );
        $record = apply_filters('football_sync_api_match_record', $record, $row);
        $record['hash'] = $this->hash($record);

        return $record;
    }

    public static function referee_external_id($name)
    {
        return self::stable_numeric_id('referee:' . self::normalized_key($name));
    }

    public static function stable_numeric_id($value)
    {
        return (int) sprintf('%u', crc32((string) $value));
    }

    private static function normalized_key($value)
    {
        $value = function_exists('remove_accents') ? remove_accents((string) $value) : (string) $value;
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value);
    }

    private function merge_season(array &$seasons, $id, $name)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return;
        }

        $name = $this->nullable_string($name);
        if (!isset($seasons[$id])) {
            $seasons[$id] = array('name' => $name ?: 'Stagione #' . $id);
        } elseif ($name && strpos($seasons[$id]['name'], 'Stagione #') === 0) {
            $seasons[$id]['name'] = $name;
        }
    }

    private function current_season_id(array $seasons)
    {
        $today = function_exists('current_time') ? current_time('Y-m-d') : gmdate('Y-m-d');
        $fallback = 0;

        foreach ($seasons as $id => $season) {
            $dates = $this->season_dates($season['name']);
            if ($dates['starts_on'] && $today >= $dates['starts_on'] && $today <= $dates['ends_on']) {
                return (int) $id;
            }
            $fallback = max($fallback, (int) $id);
        }

        return $fallback;
    }

    private function season_dates($name)
    {
        if (preg_match('/(20\d{2})\D+(20\d{2})/', (string) $name, $matches)) {
            return array(
                'starts_on' => $matches[1] . '-07-01',
                'ends_on' => $matches[2] . '-06-30',
            );
        }

        return array('starts_on' => null, 'ends_on' => null);
    }

    private function season_status(array $dates, $is_current)
    {
        if ($is_current) {
            return 'active';
        }

        $today = function_exists('current_time') ? current_time('Y-m-d') : gmdate('Y-m-d');
        if ($dates['ends_on'] && $dates['ends_on'] < $today) {
            return 'closed';
        }

        return 'new';
    }

    private function competition_type(array $row)
    {
        $source = strtolower(implode(' ', array($row['type'], $row['format_robin'], $row['format_knockout'], $row['title'])));
        $is_tournament = preg_match('/knockout|cup|coppa|torneo|tournament/', $source) === 1;
        $type = $is_tournament ? 'tournament' : 'league';

        return apply_filters('football_sync_api_competition_type', $type, $row);
    }

    private function football_type($source, array $row)
    {
        $source = strtolower((string) $source);
        $type = null;

        if (preg_match('/(?:calcio\s*(?:a\s*)?|c\s*)11\b/', $source)) {
            $type = '11';
        } elseif (preg_match('/(?:calcio\s*(?:a\s*)?|c\s*)7\b/', $source)) {
            $type = '7';
        } elseif (preg_match('/(?:calcio\s*(?:a\s*)?|c\s*)5\b|coppaca\s*5\b/', $source)) {
            $type = '5';
        }

        return apply_filters('football_sync_api_football_type', $type, $row);
    }

    private function match_status(array $row)
    {
        $special = strtolower((string) $row['special_status']);
        if (preg_match('/cancel|annull|abandon/', $special)) {
            return 'cancelled';
        }

        return (int) $row['finished'] === 1 ? 'played' : 'scheduled';
    }

    private function result_type(array $row)
    {
        $special = strtolower((string) $row['special_status']);
        if (!preg_match('/walkover|forfeit|tavolino|awarded/', $special)) {
            return 'played';
        }

        return (int) $row['home_goals'] >= (int) $row['away_goals']
            ? 'walkover_home'
            : 'walkover_away';
    }

    private function split_datetime($value)
    {
        if (!$value || strpos($value, '0000-00-00') === 0) {
            return array('date' => null, 'time' => null);
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return array('date' => null, 'time' => null);
        }

        return array('date' => date('Y-m-d', $timestamp), 'time' => date('H:i', $timestamp));
    }

    private function rating($value)
    {
        $value = (int) $value;
        return $value >= 1 && $value <= 5 ? $value : null;
    }

    private function numeric_list($value)
    {
        return array_values(array_unique(array_filter(array_map('intval', $this->text_list($value)))));
    }

    private function text_list($value)
    {
        $value = $this->structured_value($value);
        if (is_array($value)) {
            $flat = array();
            array_walk_recursive($value, static function ($item) use (&$flat) {
                if (is_scalar($item) && trim((string) $item) !== '') {
                    $flat[] = trim((string) $item);
                }
            });
            return $flat;
        }

        if (!is_scalar($value) || trim((string) $value) === '') {
            return array();
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;|]+/', (string) $value))));
    }

    private function structured_value($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $unserialized = maybe_unserialize($value);
        if ($unserialized !== $value) {
            return $unserialized;
        }

        if (is_string($value) && (($value[0] ?? '') === '{' || ($value[0] ?? '') === '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    private function find_value(array $sources, array $keys)
    {
        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }
            foreach ($source as $key => $value) {
                $normalized_key = strtolower(ltrim((string) $key, '_'));
                foreach ($keys as $candidate) {
                    if ($normalized_key === strtolower(ltrim($candidate, '_'))) {
                        return is_scalar($value) ? $this->nullable_string($value) : null;
                    }
                }
            }
        }

        return null;
    }

    private function hash(array $record)
    {
        unset($record['hash'], $record['updated_at']);
        $this->sort_recursive($record);
        $json = function_exists('wp_json_encode') ? wp_json_encode($record) : json_encode($record);

        return hash('sha256', $json);
    }

    private function sort_recursive(array &$value)
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->sort_recursive($item);
            }
        }
        unset($item);

        if ($value && array_keys($value) !== range(0, count($value) - 1)) {
            ksort($value);
        }
    }

    private function nullable_string($value)
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function nullable_int($value)
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullable_float($value)
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function absolute_url($value)
    {
        $value = $this->nullable_string($value);
        if (!$value) {
            return null;
        }

        if (strpos($value, '//') === 0) {
            $value = (is_ssl() ? 'https:' : 'http:') . $value;
        } elseif (!preg_match('#^https?://#i', $value)) {
            $value = home_url('/' . ltrim($value, '/'));
        }

        return esc_url_raw($value);
    }
}
