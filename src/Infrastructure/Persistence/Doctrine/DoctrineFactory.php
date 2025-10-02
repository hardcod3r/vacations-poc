<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

final class DoctrineFactory
{
    public static function create(): EntityManagerInterface
    {
        $isDev = ($_ENV['APP_ENV'] ?? 'dev') !== 'prod';

        $config = ORMSetup::createAttributeMetadataConfiguration(
            [
                __DIR__ . '/Model',
            ],
            $isDev,
        );

        // extract env vars safely
        $host = isset($_ENV['DB_HOST']) && \is_scalar($_ENV['DB_HOST']) ? (string) $_ENV['DB_HOST'] : 'db';
        $port = isset($_ENV['DB_PORT']) && \is_numeric($_ENV['DB_PORT']) ? (int) $_ENV['DB_PORT'] : 5432;
        $user = isset($_ENV['DB_USERNAME']) && \is_scalar($_ENV['DB_USERNAME']) ? (string) $_ENV['DB_USERNAME'] : 'vacation';
        $password = isset($_ENV['DB_PASSWORD']) && \is_scalar($_ENV['DB_PASSWORD']) ? (string) $_ENV['DB_PASSWORD'] : 'secret';
        $dbname = isset($_ENV['DB_NAME']) && \is_scalar($_ENV['DB_NAME']) ? (string) $_ENV['DB_NAME'] : 'vacation';

        /** @var array{
         *     driver: 'pdo_pgsql',
         *     host: string,
         *     port: int,
         *     user: string,
         *     password: string,
         *     dbname: string
         * } $params
         */
        $params = [
            'driver' => 'pdo_pgsql',
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'dbname' => $dbname,
        ];

        /** @var Connection $conn */
        $conn = DriverManager::getConnection($params);

        return new EntityManager($conn, $config);
    }
}
