<?php

return [
    'provider' => 'nominatim',
    'endpoint' => getenv('GEOCODING_ENDPOINT') ?: 'https://nominatim.openstreetmap.org/search',
    'user_agent' => getenv('GEOCODING_USER_AGENT') ?: 'arbitri-app/1.0 (local development)',
    'timeout' => max(1, (int)(getenv('GEOCODING_TIMEOUT') ?: 8))
];
