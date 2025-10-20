<?php

declare(strict_types=1);

namespace Lane4core\DbConnection;

use Lane4core\DbConnection\support\MySql;
use Lane4core\DbConnection\support\Postgres;
use Lane4core\DbConnection\support\SqLite;
use Lane4core\Contract\Database\DbConnectionFactoryInterface;
use Lane4core\Contract\Database\DbConnectionInterface;
use InvalidArgumentException;

/**
 * Factory for creating PDO database connections.
 */
final class DbConnection implements DbConnectionFactoryInterface
{
    /**
     * Creates a database connection instance based on the specified driver and configuration.
     *
     * @param string $driver The database driver to use (e.g., MySQL, PostgresSQL, SQLite).
     * @param array{
     *     host?: string,
     *     user?: string,
     *     password?: string,
     *     database?: string,
     *     port?: int,
     *     path?: string,
     *     options?: array<int, mixed>
     * } $config The configuration array for the database connection.
     * @return DbConnectionInterface The database connection instance for the specified driver.
     *
     * @throws InvalidArgumentException If the driver is unsupported or the required configuration is missing.
     */
    public function create(string $driver, array $config): DbConnectionInterface
    {
        return match ($driver) {
            DbConnectionFactoryInterface::DRIVER_MYSQL => new MySql(
                ...$this->extractServerConfig($config, 3306)
            ),
            DbConnectionFactoryInterface::DRIVER_PGSQL => new Postgres(
                ...$this->extractServerConfig($config, 5432)
            ),
            DbConnectionFactoryInterface::DRIVER_SQLITE => new SqLite(
                $config['path'] ?? throw new InvalidArgumentException('Missing path'),
                $config['options'] ?? []
            ),
            default => throw new InvalidArgumentException("Unsupported driver: {$driver}")
        };
    }

    public function getSupportedDrivers(): array
    {
        return [
            DbConnectionFactoryInterface::DRIVER_MYSQL,
            DbConnectionFactoryInterface::DRIVER_PGSQL,
            DbConnectionFactoryInterface::DRIVER_SQLITE,
        ];
    }

    public function supportsDriver(string $driver): bool
    {
        return in_array($driver, $this->getSupportedDrivers(), true);
    }

    /**
     * Config for MySQL und PostgresSQL
     * @param array<string, mixed> $config The configuration array for the database connection.
     * @return array<string, mixed>
     */
    private function extractServerConfig(array $config, int $defaultPort): array
    {
        return [
            'host' => $config['host'] ?? throw new InvalidArgumentException('Missing host'),
            'user' => $config['user'] ?? throw new InvalidArgumentException('Missing user'),
            'password' => $config['password'] ?? throw new InvalidArgumentException('Missing password'),
            'database' => $config['database'] ?? throw new InvalidArgumentException('Missing database'),
            'port' => $config['port'] ?? $defaultPort,
            'options' => $config['options'] ?? [],
        ];
    }
}
