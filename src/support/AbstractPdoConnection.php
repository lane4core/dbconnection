<?php

declare(strict_types=1);

namespace Lane4core\DbConnection\support;

use Lane4core\Contract\Database\DbConnectionInterface;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Abstract base class for PDO-based database connections.
 * Contains common logic for all database drivers.
 */
abstract class AbstractPdoConnection implements DbConnectionInterface
{
    protected ?PDO $pdo = null;
    protected ?string $databaseName = null;

    /**
     * Creates the PDO connection with the given parameters.
     *
     * @param string $dsn The Data Source Name
     * @param string|null $user The username (null for SQLite)
     * @param string|null $password The password (null for SQLite)
     * @param array<int, mixed> $options PDO options
     * @param string $databaseName Name of the database
     * @throws RuntimeException On connection error
     */
    protected function connect(
        string $dsn,
        ?string $user,
        ?string $password,
        array $options,
        string $databaseName
    ): void {
        try {
            $this->pdo = new PDO(
                $dsn,
                $user,
                $password,
                array_replace(DbConnectionInterface::DEFAULT_OPTIONS, $options)
            );
            $this->databaseName = $databaseName;
        } catch (PDOException $e) {
            throw new RuntimeException(
                sprintf(
                    'Could not connect to %s database: %s',
                    $this->getDriverName(),
                    $e->getMessage()
                ),
                (int)$e->getCode(),
                $e
            );
        }
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            throw new RuntimeException('No active database connection');
        }

        return $this->pdo;
    }

    public function isConnected(): bool
    {
        try {
            $this->pdo();
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Restores the connection to the database.
     * Must be implemented by derived classes, as each driver
     * requires different connection parameters.
     *
     * @throws RuntimeException On connection error
     */
    abstract public function reconnect(): void;

    public function beginTransaction(): void
    {
        try {
            $this->pdo()->beginTransaction();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to begin transaction: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function commit(): void
    {
        try {
            $this->pdo()->commit();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to commit transaction: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function rollback(): void
    {
        try {
            $this->pdo()->rollBack();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to rollback transaction: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo()->inTransaction();
    }

    public function getDatabaseName(): string
    {
        if ($this->databaseName === null) {
            throw new RuntimeException('Database name not available');
        }

        return $this->databaseName;
    }

    /**
     * Returns the name of the database driver.
     */
    abstract public function getDriverName(): string;

    /**
     * Creates the DSN string for the connection.
     * Can be overridden by child classes.
     */
    abstract protected function buildDsn(): string;

    public function getServerVersion(): string
    {
        if ($this->getDriverName() === 'sqlite') {
            $statement = $this->pdo()->query('SELECT sqlite_version()');
            $result = $statement ? $statement->fetch(PDO::FETCH_NUM) : null;
            return (string)($result[0] ?? 'unknown');
        }

        return (string)$this->pdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }
}
