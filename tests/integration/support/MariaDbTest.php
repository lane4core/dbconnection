<?php

declare(strict_types=1);

namespace Lane4core\DbConnection\Tests\integration\support;

use Lane4core\DbConnection\support\MySql;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for MySQL PDO connection.
 * Requires running MySQL Docker container from docker-compose.yml
 */
final class MariaDbTest extends TestCase
{
    private ?MySql $connection = null;
    private string $host;
    private int $port;
    private string $database;
    private string $user;
    private string $password;

    protected function setUp(): void
    {
        parent::setUp();

        // Get configuration from environment variables
        $this->host = getenv('MARIADB_HOST') ?: 'mariadb';
        $this->port = (int)getenv('MARIADB_PORT') ?: 3307;
        $this->database = getenv('MARIADB_DATABASE') ?: 'test_db';
        $this->user = getenv('MARIADB_USER') ?: 'test_user';
        $this->password = getenv('MARIADB_PASSWORD') ?: 'test_password';

        if (!$this->isMariaDbAvailable()) {
            $this->markTestSkipped('MySQL server is not available');
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
        $this->connection = new MySql(
            $this->host,
            $this->user,
            $this->password,
            $this->database,
            $this->port
        );

        $this->assertTrue($this->connection->isConnected());
        $this->assertSame('mysql', $this->connection->getDriverName());
    }

    private function isMariaDbAvailable(): bool
    {
        try {
            $connection = new MySql(
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
