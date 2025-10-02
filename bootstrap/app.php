<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));

if (is_file(dirname(__DIR__) . '/.env')) {
    $dotenv->load();
}

// Εδώ επιστρέφουμε ΜΟΝΟ το container
return require __DIR__ . '/container.php';
