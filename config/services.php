<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'azure' => [
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect' => env('AZURE_REDIRECT_URI'),
        'tenant' => env('AZURE_TENANT_ID'),
    ],

    'jira' => [
        'url' => env('JIRA_URL'),
        'username' => env('JIRA_USERNAME'),
        'token' => env('JIRA_API_TOKEN'),
        'project_key' => env('JIRA_PROJECT_KEY'),
        'status_done' => env('JIRA_STATUS_DONE', 'Done'),
        'status_reopen' => env('JIRA_STATUS_REOPEN', 'To Do'),
    ],

    'retention' => [
        'days' => (int) env('DATA_RETENTION_DAYS', 30),
        'batch_size' => (int) env('DATA_RETENTION_BATCH_SIZE', 100),
        'sleep_ms' => (int) env('DATA_RETENTION_SLEEP_MS', 50),
    ],

    'teams' => [
        'webhook_url' => env('TEAMS_WEBHOOK_URL'),
    ],

];
