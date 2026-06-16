<?php

return [
    'auth' => [
        'provider' => env('AUTH_PROVIDER', 'local'),
    ],

    'scm' => [
        'provider' => env('SCM_PROVIDER', 'github'),
        'github' => [
            'api_url' => rtrim((string) env('GITHUB_API_URL', 'https://api.github.com'), '/'),
            'token' => env('GITHUB_TOKEN'),
        ],
    ],

    'issue_tracker' => [
        'provider' => env('ISSUE_TRACKER_PROVIDER', 'jira'),
        'enabled' => (bool) env('ISSUE_TRACKER_ENABLED', false),
        'sync_statuses' => (bool) env('ISSUE_TRACKER_SYNC_STATUSES', false),
    ],

    'notifications' => [
        'provider' => env('NOTIFICATION_PROVIDER', 'teams'),
        'critical_findings_enabled' => (bool) env('CRITICAL_FINDING_NOTIFICATIONS_ENABLED', false),
    ],
];
