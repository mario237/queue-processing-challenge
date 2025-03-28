<?php

use Illuminate\Support\Str;

return [
    // Domain and path configuration
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),

    // Redis connection settings
    'use' => 'default',
    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    // Middleware for Horizon access
    'middleware' => ['web'],

    // Queue wait time thresholds (in seconds)
    'waits' => [
        'redis:default' => 60,
        'redis:orders' => 120,
        'redis:bulk-orders' => 300,
    ],

    // Job trimming times (in minutes)
    'trim' => [
        'recent' => 120,       // Keep recent jobs for 2 hours
        'pending' => 120,      // Keep pending jobs for 2 hours
        'completed' => 120,    // Keep completed jobs for 2 hours
        'recent_failed' => 10080, // Keep recent failed jobs for 7 days
        'failed' => 20160,     // Keep all failed jobs for 14 days
        'monitored' => 20160,  // Keep monitored jobs for 14 days
    ],

    // Silenced jobs (jobs not shown in completed jobs list)
    'silenced' => [
        // Add any noisy or frequent jobs to silence
        // App\Jobs\LoggingJob::class,
    ],

    // Metrics snapshot retention
    'metrics' => [
        'trim_snapshots' => [
            'job' => 48,       // Keep job metrics for 48 hours
            'queue' => 48,     // Keep queue metrics for 48 hours
        ],
    ],

    // Termination and memory management
    'fast_termination' => true, // Enable faster termination
    'memory_limit' => 256,      // Increase memory limit to 256MB

    // Default worker configuration
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'orders', 'bulk-orders'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 5,
            'maxTime' => 3600,      // Restart worker after 1 hour
            'maxJobs' => 1000,       // Restart after processing 1000 jobs
            'memory' => 256,         // Memory limit per worker
            'tries' => 3,            // Retry failed jobs 3 times
            'timeout' => 120,        // 2-minute job timeout
            'nice' => 0,
        ],
    ],

    // Environment-specific configurations
    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'queue' => ['default', 'orders', 'bulk-orders'],
            ],
            'supervisor-orders' => [
                'connection' => 'redis',
                'queue' => ['orders'],
                'balance' => 'auto',
                'maxProcesses' => 5,
                'tries' => 3,
                'timeout' => 180,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
                'queue' => ['default', 'orders', 'bulk-orders'],
            ],
            'supervisor-orders' => [
                'connection' => 'redis',
                'queue' => ['orders'],
                'balance' => 'auto',
                'maxProcesses' => 2,
                'tries' => 3,
                'timeout' => 120,
            ],
        ],
    ],
];
