<?php

declare(strict_types=1);

namespace Lane4core\DbConnection\Tests\integration\support;

use Lane4core\DbConnection\support\SqLite;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Integration tests for SQLite PDO connection.
 */
final class SqLiteTest extends TestCase
{
    private string $testDbPath;
    private ?SqLite $connection = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDbPath = sys_get_temp_dir() . '/test_sqlite_' . uniqid() . '.db';
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            $this->connection->disconnect();
            $this->connection = null;
        }

        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }

        parent::tearDown();
    }

    public function testConnectionCanBeEstablished(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $this->assertTrue($this->connection->isConnected());
        $this->assertSame('sqlite', $this->connection->getDriverName());
    }

    public function testPdoReturnsValidPdoInstance(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $pdo = $this->connection->pdo();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame('sqlite', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }

    public function testDisconnectClearsConnection(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $this->assertTrue($this->connection->isConnected());

        $this->connection->disconnect();

        $this->assertFalse($this->connection->isConnected());
    }

    public function testReconnectRestoresConnection(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $this->connection->disconnect();
        $this->assertFalse($this->connection->isConnected());

        $this->connection->reconnect();

        $this->assertTrue($this->connection->isConnected());
    }

    public function testGetDatabaseName(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $dbName = $this->connection->getDatabaseName();

        $this->assertStringContainsString('test_sqlite_', $dbName);
        $this->assertStringContainsString('.db', $dbName);
    }

    public function testGetServerVersion(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $version = $this->connection->getServerVersion();

        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+/', $version);
    }

    public function testTransactionBeginCommit(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $pdo = $this->connection->pdo();

        // Create test table
        $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, value TEXT)');

        $this->connection->beginTransaction();
        $this->assertTrue($this->connection->inTransaction());

        $pdo->exec("INSERT INTO test_table (value) VALUES ('test')");

        $this->connection->commit();
        $this->assertFalse($this->connection->inTransaction());

        // Verify data was committed
        $stmt = $pdo->query('SELECT COUNT(*) FROM test_table');
        $count = $stmt->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testTransactionRollback(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $pdo = $this->connection->pdo();

        // Create test table
        $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, value TEXT)');

        $this->connection->beginTransaction();
        $pdo->exec("INSERT INTO test_table (value) VALUES ('test')");

        $this->connection->rollback();
        $this->assertFalse($this->connection->inTransaction());

        // Verify data was rolled back
        $stmt = $pdo->query('SELECT COUNT(*) FROM test_table');
        $count = $stmt->fetchColumn();
        $this->assertSame(0, $count);
    }

    public function testGetDatabasePath(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $path = $this->connection->getDatabasePath();

        $this->assertSame($this->testDbPath, $path);
    }

    public function testVacuum(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $pdo = $this->connection->pdo();

        // Create and populate test table
        $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, value TEXT)');
        $pdo->exec("INSERT INTO test_table (value) VALUES ('test')");
        $pdo->exec("DELETE FROM test_table");

        // Should not throw exception
        $this->connection->vacuum();

        $this->assertTrue($this->connection->isConnected());
    }

    public function testSqliteOptimizationsAreApplied(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $pdo = $this->connection->pdo();

        // Check foreign keys
        $stmt = $pdo->query('PRAGMA foreign_keys');
        $foreignKeys = $stmt->fetchColumn();
        $this->assertSame(1, $foreignKeys, 'Foreign keys should be enabled');

        // Check journal mode
        $stmt = $pdo->query('PRAGMA journal_mode');
        $journalMode = $stmt->fetchColumn();
        $this->assertSame('wal', strtolower($journalMode), 'Journal mode should be WAL');
    }

    public function testPdoThrowsExceptionWhenNotConnected(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->connection->pdo();
    }

    public function testGetDatabaseNameThrowsExceptionWhenNotConnected(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database name not available');

        $this->connection->getDatabaseName();
    }

    public function testInvalidPathThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to sqlite database');

        $invalidPath = '/invalid/path/that/does/not/exist/test.db';
        new SqLite($invalidPath);
    }

    public function testCustomPdoOptions(): void
    {
        $customOptions = [
            \PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        ];

        $this->connection = new SqLite($this->testDbPath, $customOptions);
        $pdo = $this->connection->pdo();

        $errmode = $pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->assertSame(\PDO::ERRMODE_SILENT, $errmode);
    }

    public function testMemoryDatabase(): void
    {
        $this->connection = new SqLite(':memory:');

        $this->assertTrue($this->connection->isConnected());
        $this->assertSame('sqlite', $this->connection->getDriverName());

        // Should work with in-memory database
        $pdo = $this->connection->pdo();
        $pdo->exec('CREATE TABLE test (id INTEGER)');
        $pdo->exec('INSERT INTO test VALUES (1)');

        $stmt = $pdo->query('SELECT COUNT(*) FROM test');
        $count = $stmt->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testBeginTransactionFailureThrowsException(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        // Force disconnect to cause beginTransaction to fail
        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->connection->beginTransaction();
    }

    public function testCommitWithoutTransactionThrowsException(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        // Disconnect to cause commit to fail
        $this->connection->disconnect();
        $this->connection->commit();
    }

    public function testRollbackWithoutTransactionThrowsException(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        // Disconnect to cause rollback to fail
        $this->connection->disconnect();
        $this->connection->rollback();
    }

    public function testInTransactionWithoutTransactionReturnsFalse(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $this->assertFalse($this->connection->inTransaction());
    }

    public function testGetServerVersionAfterDisconnectThrowsException(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->connection->getServerVersion();
    }

    public function testReconnectFailureThrowsException(): void
    {
        // Create connection with valid path first
        $this->connection = new SqLite($this->testDbPath);

        // Manipulate connection with invalid path for reconnect failure
        // We'll use reflection to change the path to an invalid one
        $reflection = new \ReflectionClass($this->connection);
        $dbPathProperty = $reflection->getProperty('dbPath');
        $dbPathProperty->setAccessible(true);
        $dbPathProperty->setValue($this->connection, '/invalid/nonexistent/path/test.db');

        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQLite reconnection failed');

        $this->connection->reconnect();
    }

    public function testValidateDatabasePathWithNonExistentDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to sqlite database');

        // Path with deeply nested non-existent directories
        $invalidPath = '/tmp/non/existent/deep/directory/structure/that/cannot/be/created/test.db';
        new SqLite($invalidPath);
    }

    public function testValidateDatabasePathWithSystemDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to sqlite database');

        // Try to create database in system directory (usually not writable)
        $systemPath = '/usr/test.db';
        new SqLite($systemPath);
    }

    public function testDoubleBeginTransactionThrowsException(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $this->connection->beginTransaction();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to begin transaction');

        // This should trigger a PDOException which gets converted to RuntimeException
        $this->connection->beginTransaction();
    }

    public function testCommitWithoutBeginTransactionThrowsException(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to commit transaction');

        // This should trigger a PDOException since there's no active transaction
        $this->connection->commit();
    }

    public function testRollbackWithoutBeginTransactionThrowsException(): void
    {
        $this->connection = new SqLite($this->testDbPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rollback transaction');

        // This should trigger a PDOException since there's no active transaction
        $this->connection->rollback();
    }

    public function testExistingNonReadableFileThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to sqlite database');

        // Create a temporary file first
        $testFile = sys_get_temp_dir() . '/non_readable_' . uniqid() . '.db';

        // Create the file
        touch($testFile);

        // Make it non-readable (remove read permissions)
        chmod($testFile, 0000); // No permissions at all

        try {
            // This should trigger the validation error
            new SqLite($testFile);
        } finally {
            // Cleanup: restore permissions and delete file
            chmod($testFile, 0644);
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }

    public function testIsConnectedReturnsFalseWhenDisconnected(): void
    {
        $this->connection = new SqLite($this->testDbPath);
        $this->connection->disconnect();

        $this->assertFalse($this->connection->isConnected());
    }
}
