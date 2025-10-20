<?php

declare(strict_types=1);

namespace Lane4core\DbConnection\Tests\integration\support;

use Lane4core\DbConnection\support\Postgres;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Integration tests for PostgreSQL PDO connection.
 * Requires running PostgreSQL Docker container from docker-compose.yml
 */
final class PostgresTest extends TestCase
{
    private ?Postgres $connection = null;
    private string $host;
    private int $port;
    private string $database;
    private string $user;
    private string $password;

    protected function setUp(): void
    {
        parent::setUp();

        // Get configuration from environment variables
        $this->host = getenv('POSTGRES_HOST') ?: 'postgres';
        $this->port = (int)getenv('POSTGRES_PORT') ?: 5432;
        $this->database = getenv('POSTGRES_DATABASE') ?: 'test_db';
        $this->user = getenv('POSTGRES_USER') ?: 'test_user';
        $this->password = getenv('POSTGRES_PASSWORD') ?: 'test_password';

        // Skip if PostgreSQL is not available
        if (!$this->isPostgresAvailable()) {
            $this->markTestSkipped('PostgreSQL server is not available');
        }
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            try {
                // Clean up test tables
                $pdo = $this->connection->pdo();
                $pdo->exec('DROP TABLE IF EXISTS test_table');
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
            $this->connection->disconnect();
            $this->connection = null;
        }

        parent::tearDown();
    }

    public function testConnectionCanBeEstablished(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        $this->assertTrue($this->connection->isConnected());
        $this->assertSame('pgsql', $this->connection->getDriverName());
    }

    public function testPdoReturnsValidPdoInstance(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        $pdo = $this->connection->pdo();

        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertSame('pgsql', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }

    public function testDisconnectClearsConnection(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );
        $this->assertTrue($this->connection->isConnected());

        $this->connection->disconnect();

        $this->assertFalse($this->connection->isConnected());
    }

    public function testReconnectRestoresConnection(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );
        $this->connection->disconnect();
        $this->assertFalse($this->connection->isConnected());

        $this->connection->reconnect();

        $this->assertTrue($this->connection->isConnected());
    }

    public function testGetDatabaseName(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        $database = $this->connection->getDatabaseName();

        $this->assertSame($this->database, $database);
    }

    public function testGetServerVersion(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        $version = $this->connection->getServerVersion();

        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+/', $version);
    }

    public function testTransactionBeginCommit(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );
        $pdo = $this->connection->pdo();

        // Create test table
        $pdo->exec('CREATE TABLE IF NOT EXISTS test_table (id SERIAL PRIMARY KEY, value VARCHAR(255))');
        $pdo->exec('TRUNCATE TABLE test_table RESTART IDENTITY');

        $this->connection->beginTransaction();
        $this->assertTrue($this->connection->inTransaction());

        $pdo->exec("INSERT INTO test_table (value) VALUES ('test')");

        $this->connection->commit();
        $this->assertFalse($this->connection->inTransaction());

        // Verify data was committed
        $stmt = $pdo->query('SELECT COUNT(*) FROM test_table');
        $count = $stmt->fetchColumn();
        $this->assertSame(1, $count);

        // Cleanup
        $pdo->exec('DROP TABLE test_table');
    }

    public function testTransactionRollback(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );
        $pdo = $this->connection->pdo();

        // Create test table
        $pdo->exec('CREATE TABLE IF NOT EXISTS test_table (id SERIAL PRIMARY KEY, value VARCHAR(255))');
        $pdo->exec('TRUNCATE TABLE test_table RESTART IDENTITY');

        $this->connection->beginTransaction();
        $pdo->exec("INSERT INTO test_table (value) VALUES ('test')");

        $this->connection->rollback();
        $this->assertFalse($this->connection->inTransaction());

        // Verify data was rolled back
        $stmt = $pdo->query('SELECT COUNT(*) FROM test_table');
        $count = $stmt->fetchColumn();
        $this->assertSame(0, $count);

        // Cleanup
        $pdo->exec('DROP TABLE test_table');
    }

    public function testPdoThrowsExceptionWhenNotConnected(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );
        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->connection->pdo();
    }

    public function testInvalidCredentialsThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to pgsql database');

        new Postgres(
            $this->host,
            'invalid_user',
            'invalid_password',
            $this->database,
            $this->port
        );
    }

    public function testInvalidDatabaseThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to pgsql database');

        new Postgres(
            $this->host,
            $this->user,
            $this->password,
            'non_existent_database',
            $this->port
        );
    }

    public function testCustomPdoOptions(): void
    {
        $customOptions = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_CASE => \PDO::CASE_LOWER,
        ];

        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port,
            $customOptions
        );

        $pdo = $this->connection->pdo();

        // Testen Sie ein Attribut das funktioniert
        $errorMode = $pdo->getAttribute(\PDO::ATTR_ERRMODE);
        $this->assertSame(\PDO::ERRMODE_EXCEPTION, $errorMode);

        $case = $pdo->getAttribute(\PDO::ATTR_CASE);
        $this->assertSame(\PDO::CASE_LOWER, $case);
    }

    public function testBeginTransactionFailureThrowsException(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        // Force disconnect to cause beginTransaction to fail
        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->connection->beginTransaction();
    }

    public function testCommitFailureThrowsException(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        // Start transaction then disconnect to cause commit to fail
        $this->connection->beginTransaction();
        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->connection->commit();
    }

    public function testRollbackFailureThrowsException(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        // Start transaction then disconnect to cause rollback to fail
        $this->connection->beginTransaction();
        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->connection->rollback();
    }

    public function testInTransactionAfterDisconnectThrowsException(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active database connection');

        $this->connection->inTransaction();
    }

    public function testReconnectFailureThrowsException(): void
    {
        // Create connection with valid credentials first
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        // Manipulate connection with invalid credentials for reconnect failure
        // We'll use reflection to change the password to an invalid one
        $reflection = new \ReflectionClass($this->connection);
        $passwordProperty = $reflection->getProperty('password');
        $passwordProperty->setAccessible(true);
        $passwordProperty->setValue($this->connection, 'invalid_password_for_reconnect');

        $this->connection->disconnect();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PostgresSQL reconnection failed');

        $this->connection->reconnect();
    }

    public function testIsConnectedReturnsFalseWhenDisconnected(): void
    {
        $this->connection = new Postgres(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );
        $this->connection->disconnect();

        $this->assertFalse($this->connection->isConnected());
    }

    private function isPostgresAvailable(): bool
    {
        try {
            $connection = new Postgres(
                $this->host,
                $this->user,
                $this->password,
                $this->database,
                $this->port
            );
            $available = $connection->isConnected();
            $connection->disconnect();
            return $available;
        } catch (\Exception $e) {
            return false;
        }
    }
}
