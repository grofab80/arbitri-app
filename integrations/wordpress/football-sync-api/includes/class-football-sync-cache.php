<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Cache
{
    public function register_hooks()
    {
        add_action('save_post', array($this, 'clear'));
        add_action('deleted_post', array($this, 'clear'));
        add_action('update_option_football_sync_api_cache_ttl', array($this, 'clear'));
    }

    public function remember($namespace, array $filters, callable $callback, $force = false)
    {
        $key = $this->key($namespace, $filters);

        if (!$force) {
            $cached = get_transient($key);
            if ($cached !== false) {
                return $cached;
            }
        }

        $value = $callback();
        set_transient($key, $value, $this->ttl());

        return $value;
    }

    public function clear()
    {
        $generation = (int) get_option('football_sync_cache_generation', 1);
        update_option('football_sync_cache_generation', $generation + 1, false);
    }

    public function ttl()
    {
        $ttl = (int) get_option('football_sync_api_cache_ttl', 300);
        $ttl = max(60, min(3600, $ttl));

        $ttl = (int) apply_filters('football_sync_api_cache_ttl', $ttl);

        return max(60, min(3600, $ttl));
    }

    private function key($namespace, array $filters)
    {
        $generation = (int) get_option('football_sync_cache_generation', 1);
        ksort($filters);

        return 'football_sync_' . md5($generation . '|' . $namespace . '|' . wp_json_encode($filters));
    }
}
