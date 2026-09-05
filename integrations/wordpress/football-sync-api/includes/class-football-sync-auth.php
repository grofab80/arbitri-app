<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Auth
{
    public function authorize(WP_REST_Request $request)
    {
        $expected = $this->configured_key();

        if ($expected === '') {
            return new WP_Error(
                'football_sync_api_key_missing',
                'Chiave API non configurata sul server.',
                array('status' => 503)
            );
        }

        $provided = trim((string) $request->get_header('X-API-Key'));

        if ($provided === '' || !hash_equals($expected, $provided)) {
            return new WP_Error(
                'football_sync_unauthorized',
                'Chiave API assente o non valida.',
                array('status' => 401)
            );
        }

        return true;
    }

    public function configured_key()
    {
        $key = defined('FOOTBALL_SYNC_API_KEY')
            ? (string) FOOTBALL_SYNC_API_KEY
            : (string) get_option('football_sync_api_key', '');

        return trim((string) apply_filters('football_sync_api_key', $key));
    }
}
