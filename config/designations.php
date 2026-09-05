<?php

return [
    'score' => [
        'base' => 100.0,
        'rating_gap_penalty' => 18.0,
        'assignment_load_penalty' => 6.0,
        'assignment_load_max_penalty' => 30.0,
        'consecutive_team_penalty' => 35.0,
        'consecutive_home_team_penalty' => 15.0,
        'distance_km_divisor' => 5.0,
        'distance_max_penalty' => 25.0
    ],
    'constraints' => [
        'minimum_minutes_between_matches' => 120
    ]
];
