<?php

declare(strict_types=1);

use Application\Port\Security\CredentialsStore;
use Application\Port\Security\JwtIssuer;
use Application\Port\Security\PasswordHasher;
use Application\Port\System\Clock;
use Application\UseCase\Auth\LoginEmployee;
use Application\UseCase\Auth\Logout;
use Application\UseCase\Auth\RefreshSession;
use Application\UseCase\UserManagement;
use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Domain\Employee\Repository\EmployeeRepositoryInterface;
use Domain\Vacation\Repository\VacationRequestRepositoryInterface;
use Infrastructure\Http\ExceptionMapper;
use Infrastructure\Http\Middleware\AuthenticationMiddleware;
use Infrastructure\Http\Middleware\ErrorHandlerMiddleware;
use Infrastructure\Http\Middleware\RateLimitMiddleware;
use Infrastructure\Http\Responder;
use Infrastructure\Persistence\Doctrine\Auth\CredentialsRepository;
use Infrastructure\Persistence\Doctrine\Auth\RefreshTokenRepository;
use Infrastructure\Persistence\Doctrine\DoctrineEmployeeRepository;
use Infrastructure\Persistence\Doctrine\DoctrineFactory;
use Infrastructure\Persistence\Doctrine\DoctrineVacationRequestRepository;
use Infrastructure\Security\CredentialsStoreDoctrineAdapter;
use Infrastructure\Security\JwtIssuerFirebase;
use Infrastructure\Security\PasswordHasherArgon2id;
use Infrastructure\System\SystemClock;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Predis\Client as RedisClient;

function envString(mixed $value, string $default = ''): string
{
    return is_string($value) && $value !== '' ? $value : $default;
}

function envNullableString(mixed $value): ?string
{
    return is_string($value) && $value !== '' ? $value : null;
}

function envInt(mixed $value, int $default): int
{
    if (is_int($value)) {
        return $value;
    }
    if (is_string($value) && $value !== '') {
        $i = filter_var($value, FILTER_VALIDATE_INT);
        if ($i !== false) {
            return $i;
        }
    }

    return $default;
}

function envIntInRange(mixed $value, int $default, int $min, int $max): int
{
    $i = envInt($value, $default);
    if ($i < $min || $i > $max) {
        return $default;
    }

    return $i;
}

$builder = new ContainerBuilder;

$builder->addDefinitions([

    // Core factories
    Psr17Factory::class => fn () => new Psr17Factory,

    PDO::class => function (): PDO {
        $dsn = envString($_ENV['DB_DSN'] ?? null, 'pgsql:host=db;port=5432;dbname=vacation');
        $username = envNullableString($_ENV['DB_USERNAME'] ?? null) ?? 'vacation';
        $password = envNullableString($_ENV['DB_PASSWORD'] ?? null) ?? 'secret';

        return new PDO(
            $dsn,
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    },

    // Clock
    Clock::class => fn () => new SystemClock,

    // Password hasher
    PasswordHasher::class => fn () => new PasswordHasherArgon2id,

    // JWT issuer (load keys from env or files)
    JwtIssuer::class => function (): JwtIssuer {
        $privKey = '';
        $pubKey = '';

        $privEnv = envNullableString($_ENV['JWT_PRIVATE_KEY_PEM'] ?? null);
        $privPath = envNullableString($_ENV['JWT_PRIVATE_KEY_PATH'] ?? null);

        if ($privEnv) {
            $privKey = $privEnv;
        } elseif ($privPath && is_file($privPath)) {
            $privKey = (string) file_get_contents($privPath);
        }

        $pubEnv = envNullableString($_ENV['JWT_PUBLIC_KEY_PEM'] ?? null);
        $pubPath = envNullableString($_ENV['JWT_PUBLIC_KEY_PATH'] ?? null);

        if ($pubEnv) {
            $pubKey = $pubEnv;
        } elseif ($pubPath && is_file($pubPath)) {
            $pubKey = (string) file_get_contents($pubPath);
        }

        if ($privKey === '' || $pubKey === '') {
            throw new RuntimeException('JWT keys not configured');
        }

        $kid = envString($_ENV['JWT_KID'] ?? null, 'k1');
        $iss = envString($_ENV['JWT_ISS'] ?? null, 'vacation-api');

        return new JwtIssuerFirebase($privKey, $pubKey, $kid, $iss);
    },

    CredentialsStore::class => \DI\autowire(CredentialsStoreDoctrineAdapter::class),

    // Doctrine EntityManager
    EntityManagerInterface::class => fn () => DoctrineFactory::create(),

    // Domain repositories
    EmployeeRepositoryInterface::class => fn (EntityManagerInterface $em) => new DoctrineEmployeeRepository($em),
    VacationRequestRepositoryInterface::class => fn (EntityManagerInterface $em) => new DoctrineVacationRequestRepository($em),

    // Auth repositories
    CredentialsRepository::class => fn (EntityManagerInterface $em) => new CredentialsRepository($em),
    RefreshTokenRepository::class => fn (EntityManagerInterface $em) => new RefreshTokenRepository($em),

    // UseCases - Auth
    LoginEmployee::class => fn (
        EmployeeRepositoryInterface $emp,
        CredentialsRepository $creds,
        RefreshTokenRepository $tokens,
        PasswordHasher $hasher,
        JwtIssuer $jwt,
        Clock $clock,
    ) => new LoginEmployee($emp, $creds, $tokens, $hasher, $jwt, $clock),

    RefreshSession::class => fn (
        RefreshTokenRepository $tokens,
        EmployeeRepositoryInterface $emp,
        JwtIssuer $jwt,
        Clock $clock,
    ) => new RefreshSession($tokens, $emp, $jwt, $clock),

    Logout::class => fn (RefreshTokenRepository $tokens) => new Logout($tokens),

    AuthenticationMiddleware::class => fn (JwtIssuer $jwt) => new AuthenticationMiddleware($jwt),

    // UseCases
    UserManagement::class => fn (
        EmployeeRepositoryInterface $repo,
        VacationRequestRepositoryInterface $vac,
    ) => new UserManagement($repo, $vac),

    // Infra helpers
    Responder::class => fn (Psr17Factory $rf) => new Responder($rf),

    // Logger
    Logger::class => function (): Logger {
        $logger = new Logger('app');
        $logFile = dirname(__DIR__).'/storage/logs/app.log';

        if (! is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

        return $logger;
    },

    // Error handling
    ExceptionMapper::class => fn (Psr17Factory $rf, Logger $logger) => new ExceptionMapper($rf, $logger),
    ErrorHandlerMiddleware::class => fn (ExceptionMapper $mapper) => new ErrorHandlerMiddleware($mapper),

    // Redis + Rate limit with safe ints
    RedisClient::class => function (): RedisClient {
        $host = envString($_ENV['REDIS_HOST'] ?? null, 'redis');
        $port = envIntInRange($_ENV['REDIS_PORT'] ?? null, 6379, 1, 65535);

        return new RedisClient(['host' => $host, 'port' => $port]);
    },

    RateLimitMiddleware::class => fn (RedisClient $redis) => new RateLimitMiddleware(
        $redis,
        max(0, envInt($_ENV['RATE_LIMIT'] ?? null, 100)),  // 0 επιτρέπει “χωρίς όριο” αν το υποστηρίζεις
        max(1, envInt($_ENV['RATE_WINDOW'] ?? null, 60)),  // τουλάχιστον 1s παράθυρο
    ),
]);

return $builder->build();
