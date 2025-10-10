<?php

declare(strict_types=1);

namespace Lane4core\DbConnection\support;

use RuntimeException;

/**
 * SQLite database connection.
 */
final class SqLite extends AbstractPdoConnection
{
    private string $dbPath;
    /** @var array<int|mixed> */
    private array $options;

    /**
     * @param string $dbPath The file path to the SQLite database
     * @param array<int|mixed> $options Additional PDO options
     * @throws RuntimeException On connection error
     */
    public function __construct(string $dbPath, array $options = [])
    {
        $this->dbPath = $dbPath;
        $this->options = $options;

        // Validate a path before attempting connection
        $this->validateDatabasePath($dbPath);

        // SQLite has no User/Password
        $this->connect($this->buildDsn(), null, null, $options, basename($dbPath));

        // SQLite-specific optimizations
        $this->applySqliteOptimizations();
    }

    public function getDriverName(): string
    {
        return 'sqlite';
    }

    protected function buildDsn(): string
    {
        return sprintf('sqlite:%s', $this->dbPath);
    }

    public function disconnect(): void
    {
        $this->pdo = null;
        $this->databaseName = null;
    }

    public function reconnect(): void
    {
        $this->disconnect();

        try {
            $this->connect($this->buildDsn(), null, null, $this->options, basename($this->dbPath));
            $this->applySqliteOptimizations();
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                sprintf('SQLite reconnection failed: %s', $e->getMessage()),
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Validates that the database path is accessible.
     *
     * @throws RuntimeException If the path is invalid or inaccessible
     */
    private function validateDatabasePath(string $dbPath): void
    {
        // Skip validation for in-memory databases
        if ($dbPath === ':memory:') {
            return;
        }

        $directory = dirname($dbPath);

        // Check if the directory exists and is writable
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('Could not connect to sqlite database');
        }

        // If the file exists, check if it's readable
        if (file_exists($dbPath) && !is_readable($dbPath)) {
            throw new RuntimeException('Could not connect to sqlite database');
        }
    }

    /**
     * Applies SQLite-specific performance optimizations.
     */
    private function applySqliteOptimizations(): void
    {
        $this->pdo()->exec('PRAGMA foreign_keys = ON');
        $this->pdo()->exec('PRAGMA journal_mode = WAL');
        $this->pdo()->exec('PRAGMA synchronous = NORMAL');
        $this->pdo()->exec('PRAGMA temp_store = MEMORY');
        $this->pdo()->exec('PRAGMA mmap_size = 30000000000');
    }

    /**
     * Returns the file path of the SQLite database.
     * SQLite-specific method.
     */
    public function getDatabasePath(): string
    {
        return $this->dbPath;
    }

    /**
     * Executes VACUUM on the database (compression).
     * SQLite-specific maintenance function.
     */
    public function vacuum(): void
    {
        $this->pdo()->exec('VACUUM');
    }
}
