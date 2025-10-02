<?php

declare(strict_types=1);

return [
    'paths' => [
        'migrations' => 'db/migrations',
        'seeds' => 'db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST') ?: 'db',
            'port' => (int) (getenv('DB_PORT') ?: 5432),
            'name' => getenv('DB_DATABASE') ?: 'vacation',
            'user' => getenv('DB_USERNAME') ?: 'vacation',
            'pass' => getenv('DB_PASSWORD') ?: 'secret',
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation',
];
