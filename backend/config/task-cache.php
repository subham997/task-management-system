<?php

return [
    'ttl' => [
        'task_details' => (int) env('TASK_CACHE_TTL', 300),
        'active_task_count' => (int) env('ACTIVE_TASK_COUNT_CACHE_TTL', 120),
        'assignment_rules' => (int) env('ASSIGNMENT_RULES_CACHE_TTL', 300),
        'eligible_users' => (int) env('ELIGIBLE_USERS_CACHE_TTL', 120),
    ],
];
