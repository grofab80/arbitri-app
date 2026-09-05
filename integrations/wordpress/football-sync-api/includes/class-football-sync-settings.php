<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Settings
{
    public static function activate()
    {
        if (!get_option('football_sync_api_key')) {
            add_option('football_sync_api_key', wp_generate_password(48, false, false), '', false);
        }
    }

    public function register_hooks()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'register_page'));
    }

    public function register_settings()
    {
        register_setting('football_sync_api', 'football_sync_api_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_key'),
            'default' => '',
        ));
        register_setting('football_sync_api', 'football_sync_api_cache_ttl', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_cache_ttl'),
            'default' => 300,
        ));
        register_setting('football_sync_api', 'football_sync_api_log_retention_days', array(
            'type' => 'integer',
            'sanitize_callback' => array($this, 'sanitize_retention_days'),
            'default' => 30,
        ));

        add_settings_section(
            'football_sync_api_security',
            'Autenticazione API',
            static function () {
                echo '<p>La chiave deve essere inviata tramite header <code>X-API-Key</code>.</p>';
            },
            'football-sync-api'
        );

        add_settings_field(
            'football_sync_api_key',
            'Chiave API',
            array($this, 'render_key_field'),
            'football-sync-api',
            'football_sync_api_security'
        );

        add_settings_section(
            'football_sync_api_operations',
            'Prestazioni e log',
            static function () {
                echo '<p>Configurazione cache delle risposte e conservazione dei log tecnici.</p>';
            },
            'football-sync-api'
        );

        add_settings_field(
            'football_sync_api_cache_ttl',
            'Durata cache',
            array($this, 'render_cache_ttl_field'),
            'football-sync-api',
            'football_sync_api_operations'
        );

        add_settings_field(
            'football_sync_api_log_retention_days',
            'Conservazione log',
            array($this, 'render_retention_field'),
            'football-sync-api',
            'football_sync_api_operations'
        );
    }

    public function register_page()
    {
        add_options_page(
            'Football Sync API',
            'Football Sync API',
            'manage_options',
            'football-sync-api',
            array($this, 'render_page')
        );
    }

    public function sanitize_key($value)
    {
        $value = preg_replace('/[^A-Za-z0-9_-]/', '', trim((string) $value));

        if (strlen($value) < 32) {
            add_settings_error(
                'football_sync_api_key',
                'football_sync_api_key_too_short',
                'La chiave API deve contenere almeno 32 caratteri.',
                'error'
            );
            return (string) get_option('football_sync_api_key', '');
        }

        return $value;
    }

    public function render_key_field()
    {
        if (defined('FOOTBALL_SYNC_API_KEY')) {
            echo '<code>Configurata tramite FOOTBALL_SYNC_API_KEY in wp-config.php</code>';
            return;
        }

        $value = (string) get_option('football_sync_api_key', '');
        echo '<input type="text" class="regular-text code" name="football_sync_api_key" value="' . esc_attr($value) . '" autocomplete="off">';
        echo '<p class="description">Usare una chiave casuale di almeno 32 caratteri. Modificarla invalida subito i client esistenti.</p>';
    }

    public function sanitize_cache_ttl($value)
    {
        return max(60, min(3600, (int) $value));
    }

    public function sanitize_retention_days($value)
    {
        return max(1, min(365, (int) $value));
    }

    public function render_cache_ttl_field()
    {
        $value = (int) get_option('football_sync_api_cache_ttl', 300);
        echo '<input type="number" min="60" max="3600" step="1" name="football_sync_api_cache_ttl" value="' . esc_attr($value) . '"> secondi';
        echo '<p class="description">Valore consentito da 60 a 3600 secondi.</p>';
    }

    public function render_retention_field()
    {
        $value = (int) get_option('football_sync_api_log_retention_days', 30);
        echo '<input type="number" min="1" max="365" step="1" name="football_sync_api_log_retention_days" value="' . esc_attr($value) . '"> giorni';
        echo '<p class="description">I log piu vecchi vengono eliminati automaticamente.</p>';
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>Football Sync API</h1>';
        settings_errors('football_sync_api_key');
        echo '<form action="options.php" method="post">';
        settings_fields('football_sync_api');
        do_settings_sections('football-sync-api');
        submit_button();
        echo '</form>';
        echo '<p>Endpoint di verifica: <code>' . esc_html(rest_url('football-sync/v1/info')) . '</code></p>';
        echo '</div>';
    }
}
